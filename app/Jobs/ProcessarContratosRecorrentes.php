<?php

namespace App\Jobs;

use App\Models\ContratoServico;
use App\Models\Financeiro;
use App\Models\OrdemServico;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processa contratos de serviço recorrente que estão prontos para agendamento.
 * Gera automaticamente OS e/ou lançamento financeiro conforme configuração.
 */
class ProcessarContratosRecorrentes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $contratos = ContratoServico::paraAgendar()->get();

        Log::info("[ContratosRecorrentes] Processando {$contratos->count()} contrato(s).");

        foreach ($contratos as $contrato) {
            try {
                $this->processarContrato($contrato);
            } catch (\Throwable $e) {
                Log::error("[ContratosRecorrentes] Erro no contrato #{$contrato->id}: {$e->getMessage()}");
            }
        }
    }

    private function processarContrato(ContratoServico $contrato): void
    {
        // Verificar se já ultrapassou data_fim
        if ($contrato->data_fim && $contrato->data_fim->isPast()) {
            $contrato->update(['status' => 'encerrado']);
            Log::info("[ContratosRecorrentes] Contrato #{$contrato->id} encerrado (data_fim atingida).");
            return;
        }

        // Gerar OS automática
        if ($contrato->gerar_os_automatica) {
            $os = OrdemServico::create([
                'cadastro_id' => $contrato->cadastro_id,
                'contrato_servico_id' => $contrato->id,
                'tipo_servico' => $contrato->tipo_servico ?? 'Serviço Recorrente',
                'descricao_servico' => "Contrato: {$contrato->titulo}",
                'status' => 'aberta',
                'data_abertura' => now(),
                'data_prevista' => now()->addDays(7),
                'valor_total' => $contrato->valor,
                'observacoes' => "Gerada automaticamente pelo contrato #{$contrato->id}",
            ]);

            Log::info("[ContratosRecorrentes] OS #{$os->numero_os} criada para contrato #{$contrato->id}.");
        }

        // Gerar financeiro automático
        if ($contrato->gerar_financeiro_automatico) {
            Financeiro::create([
                'cadastro_id' => $contrato->cadastro_id,
                'tipo' => 'entrada',
                'descricao' => "Contrato Recorrente: {$contrato->titulo}",
                'valor' => $contrato->valor,
                'data' => now(),
                'data_vencimento' => now()->day($contrato->dia_vencimento),
                'status' => 'pendente',
            ]);

            Log::info("[ContratosRecorrentes] Financeiro gerado para contrato #{$contrato->id}.");
        }

        // Calcular próximo agendamento
        $contrato->calcularProximoAgendamento()->save();
    }
}
