<?php

namespace App\Actions\Financeiro;

use App\Models\Orcamento;

class CalculateOrcamentoTotalsAction
{
    /**
     * Executa o cálculo pesado de impostos, comissões e totais do orçamento.
     * Fim da gambiarra no Model, isso aqui é Padrão Premium.
     */
    public function execute(Orcamento $orcamento): void
    {
        // Pega a soma de todos os itens do orçamento (relação já deve existir)
        $subtotal = $orcamento->itens()->sum('subtotal');

        // Pega o desconto, se não existir é 0
        $desconto = $orcamento->desconto_valor ?? 0;

        // Calcula o Total
        $total = $subtotal - $desconto;

        // Lógica de Pró-labore e Comissão
        $comissao = 0;
        if ($orcamento->taxa_comissao > 0) {
            $comissao = $total * ($orcamento->taxa_comissao / 100);
        }

        // Atualiza 'quietly' para não disparar os Observers infinitamente e causar loop
        $orcamento->updateQuietly([
            'valor_subtotal' => $subtotal,
            'valor_total' => $total,
            'valor_comissao' => $comissao,
        ]);
    }
}
