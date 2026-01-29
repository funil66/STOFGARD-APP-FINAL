<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Financeiro;
use App\Models\Estoque;
use App\Models\Agenda;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class StofgardSystem
{
    /**
     * Transforma Orçamento em OS e Gera Financeiro Pendente
     */
    public function aprovarOrcamento(Orcamento $orcamento)
    {
        return DB::transaction(function () use ($orcamento) {
            // 1. Atualiza Status
            $orcamento->update(['status' => 'aprovado']);

            // 1.1 Gerar Número da OS Sequencial
            $ano = date('Y');
            $ultimo = OrdemServico::whereYear('created_at', $ano)->max('id') ?? 0;
            $numeroOS = "OS-{$ano}." . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);

            // 2. Cria Ordem de Serviço
            $os = OrdemServico::create([
                'cadastro_id' => $orcamento->cadastro_id,
                'orcamento_id' => $orcamento->id,
                'numero_os' => $numeroOS, // <--- A CORREÇÃO ESTÁ AQUI
                'status' => 'pendente',
                'data_inicio' => now(),
                'descricao' => "OS gerada a partir do Orçamento #{$orcamento->numero}",
                'valor_total' => $orcamento->valor_total,
            ]);

            // Copia itens do orçamento para a OS
            foreach ($orcamento->itens as $item) {
                $os->itens()->create([
                    'produto_id' => null, // Ajustar se tiver produto real
                    'servico' => $item->item_nome, // Assumindo que a coluna na OS_ITENS é 'servico' ou 'descricao'
                    'quantidade' => $item->quantidade,
                    'valor_unitario' => $item->valor_unitario,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // 3. Gera Financeiro (A Receber)
            Financeiro::create([
                'cadastro_id' => $orcamento->cadastro_id,
                'descricao' => "Recebimento OS #{$os->id} (Orç. {$orcamento->numero})",
                'tipo' => 'receita',
                'valor' => $orcamento->valor_total,
                'data_vencimento' => $orcamento->data_validade,
                'status' => 'pendente',
                'forma_pagamento' => 'pix',
                'categoria_id' => 1,
            ]);

            return $os;
        });
    }

    /**
     * Confirma pagamento e libera a OS
     */
    public function confirmarPagamento(Financeiro $lancamento)
    {
        if ($lancamento->status === 'pago') return;

        DB::transaction(function () use ($lancamento) {
            // 1. Baixa Financeira
            $lancamento->update([
                'status' => 'pago',
                'data_pagamento' => now(),
            ]);

            // 2. Se tiver OS vinculada (pela ordem_servico_id), avança status
            if ($lancamento->ordemServico) {
                $os = $lancamento->ordemServico;
                $os->update(['status' => 'em_execucao']);
            }

            Notification::make()->title('Pagamento confirmado')->success()->send();
        });
    }

    /**
     * Finaliza OS e Baixa Estoque
     */
    public function finalizarOS(OrdemServico $os)
    {
        DB::transaction(function () use ($os) {
            $os->update(['status' => 'concluido', 'data_fim' => now()]);

            // Baixa estoque dos produtos usados (se houver)
            foreach ($os->itens as $item) {
                if ($item->produto_id) {
                    Estoque::create([
                        'produto_id' => $item->produto_id,
                        'tipo' => 'saida',
                        'quantidade' => $item->quantidade,
                        'motivo' => "OS #{$os->id} Concluída",
                        'data_movimento' => now(),
                    ]);
                }
            }
        });
    }
}
