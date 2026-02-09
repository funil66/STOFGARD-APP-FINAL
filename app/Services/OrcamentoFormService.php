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
     * Recalcula o valor total do orçamento somando os subtotais dos itens.
     */
    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        $itens = $get('itens') ?? [];

        $total = collect($itens)->sum(fn($item) => floatval($item['subtotal'] ?? 0));

        $set('valor_total', $total);

        // Se houver vendedor selecionado, recalcula a comissão
        $vendedorId = $get('vendedor_id');
        if ($vendedorId) {
            $vendedor = \App\Models\Cadastro::find($vendedorId);
            if ($vendedor && $vendedor->comissao_percentual > 0) {
                $set('comissao_vendedor', ($total * $vendedor->comissao_percentual) / 100);
            }
        }
    }
}
