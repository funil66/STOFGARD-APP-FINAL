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
        // Pega a soma de todos os itens do orçamento
        $subtotal = $orcamento->itens()->sum('subtotal');

        $valorEditado = floatval($orcamento->valor_final_editado);
        $desconto = 0;

        if ($valorEditado > 0) {
            $desconto = $subtotal - $valorEditado;
            $baseCalculo = $valorEditado;
        } else {
            $baseCalculo = $subtotal;
        }

        // Lógica de Comissão
        $comissaoVendedor = 0;
        if ($orcamento->vendedor && floatval($orcamento->vendedor->comissao_percentual) > 0) {
            $comissaoVendedor = ($baseCalculo * floatval($orcamento->vendedor->comissao_percentual)) / 100;
        }

        $comissaoLoja = 0;
        if ($orcamento->loja && floatval($orcamento->loja->comissao_percentual) > 0) {
            $comissaoLoja = ($baseCalculo * floatval($orcamento->loja->comissao_percentual)) / 100;
        }

        // Atualiza 'quietly' para não disparar os Observers infinitamente e causar loop
        $orcamento->updateQuietly([
            'valor_total' => $subtotal,
            'desconto_prestador' => $desconto,
            'comissao_vendedor' => $comissaoVendedor,
            'comissao_loja' => $comissaoLoja,
        ]);

        // Chama a sincronização com Financeiro e OS se necessário
        \App\Services\OrcamentoFormService::sincronizarValorModulos($orcamento->fresh());
    }
}
