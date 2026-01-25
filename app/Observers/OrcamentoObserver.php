<?php

namespace App\Observers;

use App\Models\Orcamento;
use App\Services\StaticPixQrCodeService;

class OrcamentoObserver
{
    /**
     * Handle the Orcamento "saving" event.
     */
    public function saving(Orcamento $orcamento): void
    {
        // Gerar número do orçamento se não existir
        if (! $orcamento->numero_orcamento) {
            $orcamento->numero_orcamento = Orcamento::gerarNumeroOrcamento();
        }
        // O cálculo do total será feito no 'saved' para garantir que os itens do repeater já foram salvos.
    }

    /**
     * Handle the Orcamento "saved" event.
     */
    public function saved(Orcamento $orcamento): void
    {
        $dispatcher = $orcamento->getEventDispatcher();
        $orcamento->unsetEventDispatcher();

        try {
            $orcamento->calcularTotal();

            // Salva os totais e limpa/gera o QR code conforme necessário
            if ($orcamento->isDirty('valor_total') || $orcamento->isDirty('valor_subtotal')) {
                $orcamento->save();
            }

            app(StaticPixQrCodeService::class)->generate($orcamento);

            // Se orçamento aprovado e ainda não existe OS, criar OS + agenda minimal para testes
            if ($orcamento->status === 'aprovado' && ! \App\Models\OrdemServico::where('orcamento_id', $orcamento->id)->exists()) {
                $os = \App\Models\OrdemServico::create([
                    'numero_os' => \App\Models\OrdemServico::gerarNumeroOS(),
                    'cliente_id' => $orcamento->cliente_id,
                    'orcamento_id' => $orcamento->id,
                    'tipo_servico' => $orcamento->tipo_servico,
                    'descricao_servico' => $orcamento->descricao_servico ?? ('Conforme orçamento '.$orcamento->numero_orcamento),
                    'data_abertura' => now(),
                    'data_prevista' => $orcamento->data_servico_agendada ?? now(),
                    'status' => 'pendente',
                    'valor_total' => $orcamento->valor_total,
                    'criado_por' => $orcamento->criado_por,
                ]);

                $agenda = \App\Models\Agenda::create([
                    'titulo' => ($orcamento->tipo_servico === 'higienizacao' ? '🧼 Higienização' : 'Serviço').' - '.($orcamento->cadastro?->nome ?? $orcamento->cliente?->nome),
                    'descricao' => $orcamento->descricao_servico ?? ('Conforme orçamento '.$orcamento->numero_orcamento),
                    'cliente_id' => $orcamento->cliente_id,
                    'cadastro_id' => $orcamento->cadastro_id ?? ($orcamento->cliente_id ? 'cliente_' . $orcamento->cliente_id : null),
                    'ordem_servico_id' => $os->id,
                    'orcamento_id' => $orcamento->id,
                    'tipo' => 'servico',
                    'data_hora_inicio' => ($orcamento->data_servico_agendada ?? now())->startOfDay(),
                    'data_hora_fim' => ($orcamento->data_servico_agendada ?? now())->startOfDay()->addHours(8),
                    'status' => 'agendado',
                    'local' => $orcamento->cliente->endereco_completo ?? 'Endereço não informado',
                    'endereco_completo' => $orcamento->cliente->endereco_completo ?? null,
                    'criado_por' => $orcamento->criado_por,
                ]);

                $os->updateQuietly(['agenda_id' => $agenda->id]);

                // 4. Criar lançamento no Financeiro (a receber)
                $transacao = \App\Models\TransacaoFinanceira::create([
                    'tipo' => 'receita',
                    'categoria' => 'servico',
                    'descricao' => sprintf(
                        'Serviço - OS %s - Cliente: %s',
                        $os->numero_os,
                        $orcamento->cadastro?->nome ?? $orcamento->cliente->nome
                    ),
                    'valor' => $orcamento->valor_total,
                    'data_transacao' => $orcamento->data_servico_agendada ?? now(),
                    'data_vencimento' => $orcamento->data_servico_agendada ?? now(),
                    'status' => 'pendente',
                    'metodo_pagamento' => $orcamento->forma_pagamento,
                    'cadastro_id' => $orcamento->cadastro_id ?? ($orcamento->cliente_id ? 'cliente_' . $orcamento->cliente_id : null),
                    'cliente_id' => $orcamento->cliente_id,
                    'ordem_servico_id' => $os->id,
                    'observacoes' => 'Lançamento automático a partir do orçamento '.$orcamento->numero_orcamento,
                    'criado_por' => substr($orcamento->criado_por ?? auth()->user()->name ?? 'system', 0, 10),
                ]);

                $os->updateQuietly(['agenda_id' => $agenda->id]);

                // 4. Criar lançamento no Financeiro (a receber)
                $transacao = \App\Models\TransacaoFinanceira::create([
                    'tipo' => 'receita',
                    'categoria' => 'servico',
                    'descricao' => sprintf(
                        'Serviço - OS %s - Cliente: %s',
                        $os->numero_os,
                        $orcamento->cliente->nome
                    ),
                    'valor' => $orcamento->valor_total,
                    'data_transacao' => $orcamento->data_servico_agendada ?? now(),
                    'data_vencimento' => $orcamento->data_servico_agendada ?? now(),
                    'status' => 'pendente',
                    'metodo_pagamento' => $orcamento->forma_pagamento,
                    'cliente_id' => $orcamento->cliente_id,
                    'ordem_servico_id' => $os->id,
                    'observacoes' => 'Lançamento automático a partir do orçamento '.$orcamento->numero_orcamento,
                    'criado_por' => substr($orcamento->criado_por ?? auth()->user()->name ?? 'system', 0, 10),
                ]);

                // Se houver parceiro definido, criar lançamento de comissão (despesa)
                if ($orcamento->parceiro_id) {
                    $percentual = $orcamento->parceiro->percentual_comissao ?? 30;
                    $comissao = round(($transacao->valor * $percentual) / 100, 2);

                    \App\Models\TransacaoFinanceira::create([
                        'tipo' => 'despesa',
                        'categoria' => 'comissao',
                        'descricao' => sprintf('Comissão a pagar - Parceiro %s - OS %s', $orcamento->parceiro->nome ?? '', $os->numero_os),
                        'valor' => $comissao,
                        'data_transacao' => now(),
                        'data_vencimento' => now(),
                        'status' => 'pendente',
                        'parceiro_id' => $orcamento->parceiro_id,
                        'transacao_pai_id' => $transacao->id,
                        'observacoes' => 'Comissão automática gerada a partir do orçamento '.$orcamento->numero_orcamento,
                        'criado_por' => substr($orcamento->criado_por ?? auth()->user()->name ?? 'system', 0, 10),
                    ]);
                }

                $os->parceiro_id = $orcamento->parceiro_id;
                $os->saveQuietly();
            }

        } finally {
            $orcamento->setEventDispatcher($dispatcher);
        }
    }
}
