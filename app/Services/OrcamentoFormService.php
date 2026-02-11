<?php

namespace App\Services;

use Filament\Forms;
use App\Models\TabelaPreco;
use Illuminate\Support\Collection;

class OrcamentoFormService
{
    /**
     * Atualiza o preço unitário e subtotal de um item quando o produto ou serviço é alterado.
     */
    public static function atualizarPrecoItem(Forms\Set $set, Forms\Get $get): void
    {
        $nomeItem = $get('item_nome');
        $tipoServico = $get('servico_tipo');

        if (!$nomeItem || !$tipoServico) {
            return;
        }

        $preco = TabelaPreco::where('nome_item', $nomeItem)
            ->where('tipo_servico', $tipoServico)
            ->value('preco_vista') ?? 0;

        $set('valor_unitario', $preco);

        $quantidade = (float) $get('quantidade');
        $set('subtotal', $quantidade * $preco);

        self::recalcularTotal($set, $get);
    }

    /**
     * Recalcula o valor total do orçamento somando os subtotals dos itens.
     */
    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        $itens = $get('itens') ?? [];

        $total = 0;

        // Itera sobre cada item para calcular o subtotal
        foreach ($itens as $uuid => $item) {
            $quantidade = floatval($item['quantidade'] ?? 0);
            $valorUnitario = floatval($item['valor_unitario'] ?? 0);
            $subtotal = $quantidade * $valorUnitario;

            // Atualiza o subtotal do item
            $set("itens.{$uuid}.subtotal", number_format($subtotal, 2, '.', ''));

            $total += $subtotal;
        }

        $set('valor_total', number_format($total, 2, '.', ''));

        // Base para comissão: valor editado (se definido) ou total calculado
        $valorEditado = floatval($get('valor_final_editado') ?? 0);
        $baseComissao = $valorEditado > 0 ? $valorEditado : $total;

        // Se houver vendedor selecionado, recalcula a comissão
        $vendedorId = $get('vendedor_id');
        if ($vendedorId) {
            $vendedor = \App\Models\Cadastro::find($vendedorId);
            if ($vendedor && $vendedor->comissao_percentual > 0) {
                $comissao = ($baseComissao * $vendedor->comissao_percentual) / 100;
                $set('comissao_vendedor', number_format($comissao, 2, '.', ''));
            } else {
                $set('comissao_vendedor', 0);
            }
        } else {
            $set('comissao_vendedor', 0);
        }

        // Recalcula comissão da loja se houver
        $lojaId = $get('loja_id');
        if ($lojaId) {
            $loja = \App\Models\Cadastro::find($lojaId);
            if ($loja && $loja->comissao_percentual > 0) {
                $comissaoLoja = ($baseComissao * $loja->comissao_percentual) / 100;
                $set('comissao_loja', number_format($comissaoLoja, 2, '.', ''));
            } else {
                $set('comissao_loja', 0);
            }
        } else {
            $set('comissao_loja', 0);
        }
    }
}
