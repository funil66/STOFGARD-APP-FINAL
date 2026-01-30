<?php

namespace App\Services;

use App\Models\Financeiro;
use App\Models\Categoria;
use App\Models\OrdemServico;

class FinanceiroService
{
    /**
     * Gera a previsão de receita baseada em uma OS recém-criada.
     */
    public function gerarPreviaReceita(OrdemServico $os): TransacaoFinanceira
    {
        // Busca a categoria de forma segura
        $categoria = Categoria::where('slug', 'venda-servico')->firstOrFail();

        return TransacaoFinanceira::create([
            'descricao'        => "Receita ref. OS #{$os->id} - " . ($os->cliente->nome ?? 'Cliente Desconhecido'),
            'categoria_id'     => $categoria->id,
            'ordem_servico_id' => $os->id,
            'cliente_id'       => $os->cliente_id,
            'valor'            => $os->valor_total,
            'data_vencimento'  => now()->addDays(30), // Pode virar config no futuro
            'status'           => 'pendente',
            'tipo'             => 'receita',
        ]);
    }
}