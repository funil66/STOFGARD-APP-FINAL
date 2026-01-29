<?php

namespace App\Observers;

use App\Models\OrdemServico;
use App\Models\Agenda;
use App\Models\TransacaoFinanceira;

class OrdemServicoObserver
{
    public function created(OrdemServico $os): void
    {
        // Se não existe agenda vinculada, cria uma agenda minimal
        if (! $os->agenda_id) {
            $dataPrevista = $os->data_prevista ?? now();

            $agenda = Agenda::create([
                'titulo' => ($os->tipo_servico === 'higienizacao' ? '🧼 Higienização' : 'Serviço').' - '.($os->cadastro?->nome ?? $os->cliente->nome ?? 'Cliente'),
                'descricao' => $os->descricao_servico ?? ('Ordem de Serviço '.$os->numero_os),
                'cliente_id' => $os->cliente_id,
                'cadastro_id' => $os->cadastro_id ?? ($os->cliente_id ? 'cliente_' . $os->cliente_id : null),
                'ordem_servico_id' => $os->id,
                'tipo' => 'servico',
                'data_hora_inicio' => $dataPrevista,
                'data_hora_fim' => $dataPrevista->copy()->addHours(8),
                'status' => 'agendado',
                'local' => $os->cliente->endereco_completo ?? 'Endereço não informado',
                'endereco_completo' => $os->cliente->endereco_completo ?? null,
                'criado_por' => $os->criado_por,
            ]);

            $os->updateQuietly(['agenda_id' => $agenda->id]);
        }

        // Se não existe lançamento financeiro vinculado, cria um (receita)
        if (! \App\Models\TransacaoFinanceira::where('ordem_servico_id', $os->id)->exists()) {
            $transacao = TransacaoFinanceira::create([
                'tipo' => 'receita',
                'categoria' => 'servico',
                'descricao' => sprintf('Serviço - OS %s - Cliente: %s', $os->numero_os, $os->cadastro?->nome ?? $os->cliente->nome ?? ''),
                'valor' => $os->valor_total ?? 0,
                'data_transacao' => $os->data_abertura ?? now(),
                'data_vencimento' => $os->data_prevista ?? now(),
                'status' => 'pendente',
                'metodo_pagamento' => $os->forma_pagamento,
                'cadastro_id' => $os->cadastro_id ?? ($os->cliente_id ? 'cliente_' . $os->cliente_id : null),
                'cliente_id' => $os->cliente_id,
                'ordem_servico_id' => $os->id,
                'observacoes' => 'Lançamento automático a partir da OS '.$os->numero_os,
                'criado_por' => substr($os->criado_por ?? 'system', 0, 10),
            ]);

            // Se tem parceiro, cria lançamento de comissão (despesa)
            if ($os->parceiro_id) {
                $percentual = $os->percentual_comissao_os ?? ($os->parceiro->percentual_comissao ?? 30);
                $comissao = round(($transacao->valor * $percentual) / 100, 2);

                TransacaoFinanceira::create([
                    'tipo' => 'despesa',
                    'categoria' => 'comissao',
                    'descricao' => sprintf('Comissão a pagar - Parceiro %s - OS %s', $os->parceiro->nome ?? '', $os->numero_os),
                    'valor' => $comissao,
                    'data_transacao' => now(),
                    'data_vencimento' => now(),
                    'status' => 'pendente',
                    'parceiro_id' => $os->parceiro_id,
                    'transacao_pai_id' => $transacao->id,
                    'observacoes' => 'Comissão automática gerada a partir da OS '.$os->numero_os,
                    'criado_por' => substr($os->criado_por ?? 'system', 0, 10),
                ]);
            }
        }
    }

    public function updated(OrdemServico $os)
    {
        if ($os->isDirty('status') && $os->status === 'concluido') {
            foreach ($os->itens as $item) {
                if ($item->produto_id) {
                    \App\Models\Estoque::create([
                        'produto_id' => $item->produto_id,
                        'tipo' => 'saida',
                        'quantidade' => $item->quantidade,
                        'motivo' => "Uso na OS #{$os->id}",
                        'data_movimento' => now(),
                    ]);
                }
            }
        }
    }
}

