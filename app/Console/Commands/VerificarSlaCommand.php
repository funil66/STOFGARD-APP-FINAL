<?php

namespace App\Console\Commands;

use App\Models\OrdemServico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Verifica ordens de serviço com SLA próximo do vencimento e envia alertas.
 */
class VerificarSlaCommand extends Command
{
    protected $signature = 'os:verificar-sla';

    protected $description = 'Verifica OS com SLA próximo do vencimento e notifica responsáveis';

    public function handle(): int
    {
        $osComSla = OrdemServico::whereNotNull('prazo_sla_horas')
            ->whereNull('sla_alerta_enviado_em')
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->whereNotNull('data_abertura')
            ->get();

        $alertados = 0;

        foreach ($osComSla as $os) {
            $limiteHoras = $os->prazo_sla_horas;
            $horasDecorridas = $os->data_abertura->diffInHours(now());
            $percentualConsumido = $limiteHoras > 0 ? ($horasDecorridas / $limiteHoras) * 100 : 0;

            // Alertar quando atingir 80% do SLA
            if ($percentualConsumido >= 80) {
                $this->enviarAlertaSla($os, $percentualConsumido, $horasDecorridas, $limiteHoras);
                $os->update(['sla_alerta_enviado_em' => now()]);
                $alertados++;
            }
        }

        if ($alertados > 0) {
            $this->info("✅ {$alertados} alerta(s) de SLA enviado(s).");
            Log::info("[SLA] {$alertados} alerta(s) de SLA enviado(s).");
        } else {
            $this->info('Nenhum alerta de SLA necessário.');
        }

        return self::SUCCESS;
    }

    private function enviarAlertaSla(OrdemServico $os, float $percentual, int $horasDecorridas, int $limiteHoras): void
    {
        $horasRestantes = max(0, $limiteHoras - $horasDecorridas);
        $vencido = $horasRestantes <= 0;

        $titulo = $vencido
            ? "🚨 SLA VENCIDO — OS {$os->numero_os}"
            : "⚠️ SLA em risco — OS {$os->numero_os}";

        $mensagem = $vencido
            ? "A OS {$os->numero_os} ultrapassou o prazo de SLA ({$limiteHoras}h). Horas decorridas: {$horasDecorridas}h."
            : "A OS {$os->numero_os} está a {$horasRestantes}h do vencimento do SLA ({$limiteHoras}h). Consumido: " . round($percentual) . "%.";

        // Persistir alerta para o criador da OS no canal de notificações do Filament
        $user = \App\Models\User::find($os->criado_por);

        if ($user) {
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'App\\Notifications\\SlaAlertNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $titulo,
                    'body' => $mensagem,
                    'os_id' => $os->id,
                    'numero_os' => $os->numero_os,
                    'percentual_sla' => round($percentual),
                    'vencido' => $vencido,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::warning("[SLA] {$titulo}: {$mensagem}");
    }
}
