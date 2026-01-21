<?php

namespace App\Observers;

use App\Models\Inventario;
use App\Models\TransacaoFinanceira;

class InventarioObserver
{
    /**
     * Handle the Inventario "created" event.
     */
    public function created(Inventario $inventario): void
    {
        // Se o inventário tem valor, criar transação financeira (despesa)
        if ($inventario->valor_aquisicao && $inventario->valor_aquisicao > 0) {
            TransacaoFinanceira::create([
                'tipo' => 'despesa',
                'categoria' => 'operacional',
                'descricao' => "Aquisição de Equipamento: {$inventario->nome}",
                'valor' => $inventario->valor_aquisicao,
                'data_transacao' => $inventario->data_aquisicao ?? now(),
                'status' => 'confirmado',
                'metodo_pagamento' => 'dinheiro',
                'observacoes' => "Inventário ID: {$inventario->id} | Categoria: {$inventario->categoria}",
            ]);
        }
    }

    /**
     * Handle the Inventario "updated" event.
     */
    public function updated(Inventario $inventario): void
    {
        // Se o valor de aquisição foi alterado e aumentou, registrar diferença
        if ($inventario->isDirty('valor_aquisicao')) {
            $valorAntigo = $inventario->getOriginal('valor_aquisicao') ?? 0;
            $valorNovo = $inventario->valor_aquisicao ?? 0;
            $diferenca = $valorNovo - $valorAntigo;

            if ($diferenca > 0) {
                TransacaoFinanceira::create([
                    'tipo' => 'despesa',
                    'categoria' => 'operacional',
                    'descricao' => "Ajuste de Valor: {$inventario->nome} (Diferença: R$ {$diferenca})",
                    'valor' => $diferenca,
                    'data_transacao' => now(),
                    'status' => 'confirmado',
                    'metodo_pagamento' => 'ajuste',
                    'observacoes' => "Inventário ID: {$inventario->id} | Valor anterior: R$ {$valorAntigo} → Novo: R$ {$valorNovo}",
                ]);
            }
        }
    }

    /**
     * Handle the Inventario "deleted" event.
     */
    public function deleted(Inventario $inventario): void
    {
        // Opcional: registrar baixa patrimonial se tinha valor
        if ($inventario->valor_aquisicao && $inventario->valor_aquisicao > 0) {
            TransacaoFinanceira::create([
                'tipo' => 'despesa',
                'categoria' => 'operacional',
                'descricao' => "Baixa de Equipamento: {$inventario->nome}",
                'valor' => 0,
                'data_transacao' => now(),
                'status' => 'cancelado',
                'metodo_pagamento' => 'ajuste',
                'observacoes' => "Inventário ID: {$inventario->id} | Valor original: R$ {$inventario->valor_aquisicao}",
            ]);
        }
    }
}
