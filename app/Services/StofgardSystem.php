<?php

namespace App\Services;

use App\Enums\AgendaStatus;
use App\Enums\AgendaTipo;
use App\Enums\FinanceiroCategoria;
use App\Enums\FinanceiroStatus;
use App\Enums\FinanceiroTipo;
use App\Enums\OrcamentoStatus;
use App\Enums\OrdemServicoStatus;
use App\Models\Agenda;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class StofgardSystem
{
    public function __construct(
        private EstoqueService $estoqueService
    ) {}

    /**
     * Aprova um orçamento, gerando OS e previsão financeira de forma atômica.
     * Iron Code Refactoring: Remoção de magic numbers e race conditions.
     *
     * @param  int|null  $userId  ID do usuário que está aprovando (obrigatório para auditoria)
     * @param  int  $prazoVencimentoDias  Prazo em dias para vencimento (padrão: 30)
     * @param  int  $diasAteAgendamento  Dias até o agendamento (padrão: 1)
     * @param  int  $horaAgendamento  Hora do agendamento (padrão: 9)
     *
     * @throws \Exception Se o orçamento já foi aprovado ou se userId não for fornecido
     */
    public function aprovarOrcamento(
        Orcamento $orcamento,
        ?int $userId = null,
        int $prazoVencimentoDias = 30,
        int $diasAteAgendamento = 1,
        int $horaAgendamento = 9
    ): OrdemServico {
        // CORREÇÃO CRÍTICA: Não permitir operações sem usuário responsável
        if (! $userId && ! auth()->id()) {
            throw new \Exception('Não é possível aprovar orçamento sem um usuário responsável. Operação rejeitada por questão de auditoria.');
        }

        $userId = $userId ?? auth()->id();

        // 1. Validação prévia para evitar duplicidade lógica
        if ($orcamento->status === OrcamentoStatus::Aprovado->value) {
            throw new \Exception('Este orçamento já foi aprovado anteriormente.');
        }

        return DB::transaction(function () use ($orcamento, $userId, $prazoVencimentoDias, $diasAteAgendamento, $horaAgendamento) {
            // A. Criação da OS (Usando cadastro_id unificado)
            $os = OrdemServico::create([
                'orcamento_id' => $orcamento->id,
                'cadastro_id' => $orcamento->cadastro_id,
                'loja_id' => $orcamento->loja_id,
                'vendedor_id' => $orcamento->vendedor_id,
                'status' => OrdemServicoStatus::Aberta->value,
                'data_abertura' => now(),
                'tipo_servico' => $orcamento->tipo_servico ?? FinanceiroCategoria::Servico->value,
                'descricao_servico' => "Gerado a partir do Orçamento #{$orcamento->numero}",
                'valor_total' => $orcamento->valor_total,
                'criado_por' => $userId,
            ]);

            // B. Criação do Financeiro (Receita Prevista) - Usando Financeiro model com cadastro_id
            Financeiro::create([
                'descricao' => "Receita ref. OS #{$os->numero_os} - ".($orcamento->cliente->nome ?? 'Cliente'),
                'ordem_servico_id' => $os->id,
                'orcamento_id' => $orcamento->id,
                'cadastro_id' => $orcamento->cadastro_id,
                'valor' => $orcamento->valor_total,
                'data_vencimento' => now()->addDays($prazoVencimentoDias),
                'status' => FinanceiroStatus::Pendente->value,
                'tipo' => FinanceiroTipo::Entrada->value,
                'categoria' => FinanceiroCategoria::Servico->value,
            ]);

            // C. Cria Agenda (Serviço Agendado)
            Agenda::create([
                'titulo' => 'Serviço - '.($orcamento->cliente->nome ?? 'Cliente'),
                'descricao' => "OS #{$os->numero_os} via Orçamento #{$orcamento->numero}",
                'cadastro_id' => $orcamento->cadastro_id,
                'ordem_servico_id' => $os->id,
                'orcamento_id' => $orcamento->id,
                'tipo' => AgendaTipo::Servico->value,
                'data_hora_inicio' => now()->addDays($diasAteAgendamento)->setHour($horaAgendamento)->setMinute(0),
                'data_hora_fim' => now()->addDays($diasAteAgendamento)->setHour($horaAgendamento + 2)->setMinute(0),
                'status' => AgendaStatus::Agendado->value,
                'local' => $orcamento->cliente->endereco ?? 'A definir',
                'criado_por' => $userId,
            ]);

            // D. Atualiza o Orçamento
            $orcamento->update([
                'status' => OrcamentoStatus::Aprovado->value,
                'aprovado_em' => now(),
            ]);

            return $os;
        });
    }

    /**
     * Confirma pagamento e libera a OS
     */
    public function confirmarPagamento(Financeiro $lancamento): void
    {
        if ($lancamento->status === FinanceiroStatus::Pago->value) {
            return;
        }

        DB::transaction(function () use ($lancamento) {
            // 1. Baixa Financeira
            $lancamento->update([
                'status' => FinanceiroStatus::Pago->value,
                'data_pagamento' => now(),
            ]);

            // 2. Se tiver OS vinculada (pela ordem_servico_id), avança status
            if ($lancamento->ordemServico) {
                $os = $lancamento->ordemServico;
                $os->update(['status' => OrdemServicoStatus::EmExecucao->value]);
            }

            Notification::make()->title('Pagamento confirmado')->success()->send();
        });
    }

    /**
     * Finaliza OS e Baixa Estoque
     */
    public function finalizarOS(OrdemServico $os): void
    {
        DB::transaction(function () use ($os) {
            $os->update([
                'status' => OrdemServicoStatus::Concluida->value,
                'data_fim' => now(),
            ]);

            // Delega a baixa de estoque para o serviço especializado
            $this->estoqueService->baixarEstoquePorOS($os);
        });
    }
}
