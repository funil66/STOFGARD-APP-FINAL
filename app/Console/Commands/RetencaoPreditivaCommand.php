<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RetencaoPreditivaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cofre:retencao-preditiva {--meses=6}';

    protected $description = 'Varre ordens de serviço antigas e alerta para manutenção preditiva (A Máquina de Retenção)';

    public function handle()
    {
        $meses = (int) $this->option('meses');
        $dataCorte = now()->subMonths($meses)->toDateString();

        $this->info("Iniciando Máquina de Retenção (Buscando OS concluídas em {$dataCorte})");

        $tenants = \App\Models\Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            if (!in_array($tenant->plan, ['PRO', 'ELITE'])) {
                tenancy()->end();
                continue; // Feature apenas PRO/ELITE
            }

            $ordens = \App\Models\OrdemServico::where('status', 'concluida')
                ->whereDate('data_conclusao', $dataCorte)
                ->with('cliente')
                ->get();

            if ($ordens->isEmpty()) {
                tenancy()->end();
                continue;
            }

            $this->info("Tenant {$tenant->name}: Encontradas {$ordens->count()} OS antigas.");

            foreach ($ordens as $os) {
                $cliente = $os->cliente;
                if (!$cliente)
                    continue;

                // 1. Notifica o Admin no painel
                $admins = \App\Models\User::all();
                foreach ($admins as $admin) {
                    \Filament\Notifications\Notification::make()
                        ->title('Oportunidade de Retenção! 💰')
                        ->body("O serviço do cliente {$cliente->nome} (OS #{$os->numero_os}) completou {$meses} meses. Que tal oferecer uma manutenção?")
                        ->warning()
                        ->sendToDatabase($admin);
                }

                // 2. Dispara o Zap (Se o cliente tem telefone e o tenant ativou, aqui fazemos um disparo)
                $phone = preg_replace('/[^0-9]/', '', $cliente->telefone ?? '');
                if (!empty($phone) && strlen($phone) >= 10) {
                    $mensagem = "Olá {$cliente->nome}, tudo bem? Aqui é da " . ($tenant->name ?? 'empresa') . ".\n\nHá {$meses} meses realizamos um serviço pra você. Passando para lembrar que já pode estar na hora de uma manutenção preventiva para manter tudo novinho! Gostaria de agendar uma avaliação gratuita?";

                    \App\Jobs\SendWhatsAppJob::dispatch($phone, $mensagem, $tenant->slug ?? 'default')
                        ->delay(now()->addMinutes(rand(1, 60))); // Espalha os envios pra não dar bloqueio
                }
            }

            tenancy()->end();
        }

        $this->info("Operação concluída.");
    }
}
