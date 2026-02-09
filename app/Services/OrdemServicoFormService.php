<?php

namespace App\Services;

use Filament\Forms;
use App\Models\TabelaPreco;
use App\Models\Estoque;

class OrdemServicoFormService
{
    /**
     * Atualiza a descrição e garantia quando o tipo de serviço é alterado.
     */
    public static function atualizarDadosServico(Forms\Set $set, $state): void
    {
        $servico = TabelaPreco::where('tipo_servico', $state)
            ->whereNotNull('descricao_tecnica')
            ->first();

        if ($servico) {
            $set('descricao_servico', $servico->descricao_tecnica);
            $set('dias_garantia', $servico->dias_garantia);
        }
    }

    /**
     * Atualiza unidade e preço quando um item é selecionado.
     */
    public static function atualizarItem(Forms\Set $set, Forms\Get $get, $state): void
    {
        $item = TabelaPreco::where('nome_item', $state)->first();

        if ($item) {
            $set('unidade_medida', $item->unidade_medida);
            $set('valor_unitario', $item->preco_vista);
        }

        self::recalcularSubtotal($set, $get);
    }

    /**
     * Recalcula o subtotal de uma linha basedo em qtd e valor unitário.
     */
    public static function recalcularSubtotal(Forms\Set $set, Forms\Get $get): void
    {
        $qtd = (float) $get('quantidade') ?: 1;
        $unit = (float) $get('valor_unitario') ?: 0;
        $set('subtotal', $qtd * $unit);

        // Como estou dentro de um repeater, preciso chamar o recalcularTotal do pai.
        // O Filament injeta o contexto, mas 'recalcularTotal' é static no Resource ou Service.
        // A melhor forma aqui é chamar o método que varre o form, mas dentro de um closure do repeater
        // o contexto $get sobe niveis com ../../ etc. 
        // Vamos deixar o Repeater chamar o recalcularTotal global após atualização do subtotal.
    }

    /**
     * Atualiza dados do estoque quando um produto é selecionado.
     */
    public static function atualizarEstoque(Forms\Set $set, $state): void
    {
        if ($state) {
            $estoque = Estoque::find($state);
            if ($estoque) {
                $set('unidade', $estoque->unidade);
                $set('disponivel', $estoque->quantidade);
            }
        }
    }

    /**
     * Recalcula o valor total da OS somando todos os itens.
     */
    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        // Tenta pegar itens do contexto atual ou subir niveis se estiver dentro de um repeater
        $itens = $get('itens') ?? $get('../../itens') ?? [];

        if (!is_array($itens)) {
            $itens = [];
        }

        $total = collect($itens)->sum(function ($item) {
            return floatval($item['subtotal'] ?? 0);
        });

        // Tenta setar o valor total no contexto atual ou pai
        $set('valor_total', $total);
        $set('../../valor_total', $total);
    }
}
