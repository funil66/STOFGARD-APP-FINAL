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
    ) {
    }

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
    public function aprovarOrcamento(Orcamento $orcamento, int $userId, array $options = [])
    {
        // CORREÇÃO CRÍTICA: Não permitir operações sem usuário responsável
        if (!$userId && !auth()->id()) {
            throw new \Exception('Não é possível aprovar orçamento sem um usuário responsável. Operação rejeitada por questão de auditoria.');
        }

        $userId = $userId ?? auth()->id();

        // 1. Validação prévia para evitar duplicidade lógica
        if ($orcamento->status === OrcamentoStatus::Aprovado->value) {
            throw new \Exception('Este orçamento já foi aprovado anteriormente.');
        }

        return DB::transaction(function () use ($orcamento, $userId, $options) {
            // Prepara Data Prevista com Hora (se disponível)
            $dataPrevista = $options['data_servico'] ? \Carbon\Carbon::parse($options['data_servico']) : now()->addDays(2);

            if (!empty($options['data_servico']) && !empty($options['hora_inicio'])) {
                $time = \Carbon\Carbon::parse($options['hora_inicio']);
                $dataPrevista->setTimeFromTimeString($time->format('H:i:s'));
            } elseif (!empty($options['data_servico'])) {
                // Default para 09:00 se tiver data mas sem hora
                $dataPrevista->setTime(9, 0, 0);
            }

            // A. Criação da OS (Usando cadastro_id unificado)
            // OBS: O OrdemServicoObserver cria uma Agenda automaticamente aqui!
            $os = OrdemServico::create([
                'orcamento_id' => $orcamento->id,
                'cadastro_id' => $orcamento->cadastro_id,
                'loja_id' => $orcamento->loja_id,
                'vendedor_id' => $orcamento->vendedor_id,
                'id_parceiro' => $orcamento->id_parceiro,
                'status' => OrdemServicoStatus::Aberta->value,
                'data_abertura' => now(),
                'data_prevista' => $dataPrevista,
                'tipo_servico' => $orcamento->tipo_servico ?? FinanceiroCategoria::Servico->value,
                'descricao_servico' => $orcamento->descricao_servico ?? "Conforme orçamento {$orcamento->numero}",
                'valor_total' => $orcamento->valor_efetivo,
                'observacoes' => $options['observacoes'] ?? $orcamento->observacoes,
                'criado_por' => $userId,
            ]);

            // Copiar itens para OS
            foreach ($orcamento->itens as $item) {
                // Mapeia unidade para valores aceitos pelo enum
                $unidade = $item->unidade ?? $item->unidade_medida ?? 'un';
                $unidadeMapeada = $unidade === 'm2' ? 'm2' : 'unidade';

                \App\Models\OrdemServicoItem::create([
                    'ordem_servico_id' => $os->id,
                    'descricao' => $item->item_nome ?? $item->descricao_item ?? 'Serviço',
                    'quantidade' => $item->quantidade,
                    'unidade_medida' => $unidadeMapeada,
                    'valor_unitario' => $item->valor_unitario,
                    'subtotal' => $item->subtotal,
                ]);
            }

            $prazoVencimentoDias = 3;

            // B. Criação do Financeiro (Receita Prevista) - Usando Financeiro model com cadastro_id
            $dataVencimento = !empty($options['data_servico'])
                ? \Carbon\Carbon::parse($options['data_servico'])
                : now()->addDays($prazoVencimentoDias);

            Financeiro::create([
                'descricao' => "Receita OS #{$os->numero_os} - Orç. #{$orcamento->numero} - " . ($orcamento->cliente->nome ?? 'Cliente'),
                'ordem_servico_id' => $os->id,
                'orcamento_id' => $orcamento->id,
                'id_parceiro' => $orcamento->id_parceiro,
                'cadastro_id' => $orcamento->cadastro_id,
                'loja_id' => $orcamento->loja_id,
                'vendedor_id' => $orcamento->vendedor_id,
                'valor' => $orcamento->valor_efetivo,
                'desconto' => max(0, floatval($orcamento->valor_total) - floatval($orcamento->valor_efetivo)),
                'data' => !empty($options['data_servico']) ? $options['data_servico'] : now(),
                'data_vencimento' => $dataVencimento,
                'status' => FinanceiroStatus::Pendente->value,
                'tipo' => FinanceiroTipo::Entrada->value,
                'categoria' => FinanceiroCategoria::Servico->value,
                'observacoes' => implode(' | ', array_filter([
                    $orcamento->vendedor ? "Vendedor: {$orcamento->vendedor->nome}" : null,
                    $orcamento->loja ? "Loja: {$orcamento->loja->nome}" : null,
                    $orcamento->id_parceiro ? "Parceiro: {$orcamento->id_parceiro}" : null,
                ])),
            ]);

            // B2. Comissão do Vendedor (Despesa)
            if (floatval($orcamento->comissao_vendedor) > 0 && $orcamento->vendedor_id) {
                Financeiro::create([
                    'descricao' => "Comissão Vendedor - OS #{$os->numero_os} - " . ($orcamento->vendedor->nome ?? 'Vendedor'),
                    'ordem_servico_id' => $os->id,
                    'orcamento_id' => $orcamento->id,
                    'cadastro_id' => $orcamento->vendedor_id,
                    'valor' => $orcamento->comissao_vendedor,
                    'data' => now(),
                    'data_vencimento' => $dataVencimento->copy()->addDays(5),
                    'status' => FinanceiroStatus::Pendente->value,
                    'tipo' => FinanceiroTipo::Saida->value,
                    'categoria' => FinanceiroCategoria::Comissao->value,
                    'is_comissao' => true,
                    'comissao_paga' => false,
                ]);
            }

            // B3. Comissão da Loja (Despesa)
            if (floatval($orcamento->comissao_loja) > 0 && $orcamento->loja_id) {
                Financeiro::create([
                    'descricao' => "Comissão Loja - OS #{$os->numero_os} - " . ($orcamento->loja->nome ?? 'Loja'),
                    'ordem_servico_id' => $os->id,
                    'orcamento_id' => $orcamento->id,
                    'cadastro_id' => $orcamento->loja_id,
                    'valor' => $orcamento->comissao_loja,
                    'data' => now(),
                    'data_vencimento' => $dataVencimento->copy()->addDays(5),
                    'status' => FinanceiroStatus::Pendente->value,
                    'tipo' => FinanceiroTipo::Saida->value,
                    'categoria' => FinanceiroCategoria::Comissao->value,
                    'is_comissao' => true,
                    'comissao_paga' => false,
                ]);
            }

            // C. Atualiza/Cria Agenda (Evitando duplicidade do Observer)
            // Tenta buscar a agenda criada pelo Observer
            $agenda = Agenda::where('ordem_servico_id', $os->id)->first();

            if (!empty($options['data_servico'])) {
                $dataServico = \Carbon\Carbon::parse($options['data_servico']);
                $horaInicio = !empty($options['hora_inicio']) ? \Carbon\Carbon::parse($options['hora_inicio']) : \Carbon\Carbon::parse('09:00');
                $horaFim = !empty($options['hora_fim']) ? \Carbon\Carbon::parse($options['hora_fim']) : \Carbon\Carbon::parse('11:00');

                $inicio = $dataServico->copy()->setTimeFromTimeString($horaInicio->format('H:i:s'));
                $fim = $dataServico->copy()->setTimeFromTimeString($horaFim->format('H:i:s'));

                $dadosAgenda = [
                    'titulo' => 'Serviço - ' . ($orcamento->cliente->nome ?? 'Cliente'),
                    'descricao' => "OS #{$os->numero_os} via Orçamento #{$orcamento->numero}",
                    'cadastro_id' => $orcamento->cadastro_id,
                    'ordem_servico_id' => $os->id,
                    'orcamento_id' => $orcamento->id,
                    'tipo' => AgendaTipo::Servico->value,
                    'data_hora_inicio' => $inicio,
                    'data_hora_fim' => $fim,
                    'status' => AgendaStatus::Agendado->value,
                    'local' => $options['local_servico'] ?? ($orcamento->cliente->endereco ?? 'A definir'),
                    'id_parceiro' => $orcamento->id_parceiro,
                    'criado_por' => $userId,
                ];

                if ($agenda) {
                    $agenda->update($dadosAgenda);
                } else {
                    Agenda::create($dadosAgenda);
                }
            } else if ($agenda) {
                // Se o observer criou, mas não temos data definida, apenas atualiza descrição
                $agenda->update([
                    'descricao' => "OS #{$os->numero_os} via Orçamento #{$orcamento->numero}",
                    'orcamento_id' => $orcamento->id,
                    'tipo' => AgendaTipo::Servico->value,
                    'status' => AgendaStatus::Agendado->value,
                    'id_parceiro' => $orcamento->id_parceiro,
                ]);
            } else {
                // Se não tem data definida e o Observer não criou (improvável), cria um placeholder
                Agenda::create([
                    'titulo' => 'Agendar Serviço - ' . ($orcamento->cliente->nome ?? 'Cliente'),
                    'descricao' => "OS #{$os->numero_os} (Aguardando Agendamento)",
                    'cadastro_id' => $orcamento->cadastro_id,
                    'ordem_servico_id' => $os->id,
                    'orcamento_id' => $orcamento->id,
                    'tipo' => AgendaTipo::Servico->value,
                    'data_hora_inicio' => now()->addDays(2)->setHour(9)->setMinute(0),
                    'data_hora_fim' => now()->addDays(2)->setHour(11)->setMinute(0),
                    'status' => AgendaStatus::Agendado->value,
                    'local' => $orcamento->cliente->endereco ?? 'A definir',
                    'id_parceiro' => $orcamento->id_parceiro,
                    'criado_por' => $userId,
                ]);
            }

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
