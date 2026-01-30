<?php

namespace App\Services;

use App\Models\Orcamento;
use Illuminate\Support\Facades\Log;

class EstoqueService
{
    /**
     * Verifica e baixa o estoque baseado nos itens do orçamento.
     */
    public function baixarItensDeOrcamento(Orcamento $orcamento): void
    {
        // Implementação futura: Iterar sobre $orcamento->itens e reduzir do Estoque
        // Por enquanto, apenas loga a intenção.
        Log::info("Estoque: Baixa solicitada para Orçamento #{$orcamento->id}");
        
        // Exemplo futuro:
        // foreach ($orcamento->itens as $item) {
        //     $item->produto->decrement('estoque_atual', $item->quantidade);
        // }
    }
}