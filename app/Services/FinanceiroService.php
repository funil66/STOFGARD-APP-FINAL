<?php

namespace App\Services;

use App\Models\Financeiro;
use App\Models\OrdemServico;

class FinanceiroService
{
    /**
     * Gera a previsão de receita (Conta a Receber) baseada em uma OS recém-criada.
     * Usa o modelo Financeiro com cadastro_id (Cadastro Unificado).
     */
    public function gerarPreviaReceita(OrdemServico $os): Financeiro
    {
        return Financeiro::create([
            'cadastro_id' => $os->cadastro_id,
            'ordem_servico_id' => $os->id,
            'tipo' => 'entrada',
            'categoria' => 'servico',
            'descricao' => "Receita ref. OS #{$os->numero_os} - ".($os->cliente->nome ?? 'Cliente'),
            'valor' => $os->valor_total,
            'data_vencimento' => now()->addDays(30),
            'status' => 'pendente',
            'forma_pagamento' => null, // Define quando cliente pagar
        ]);
    }

    /**
     * Gera despesa/saída financeira.
     */
    public function gerarDespesa(array $dados): Financeiro
    {
        return Financeiro::create(array_merge([
            'tipo' => 'saida',
            'status' => 'pendente',
        ], $dados));
    }

    /**
     * Marca uma transação como paga.
     */
    public function baixar(Financeiro $financeiro, ?string $formaPagamento = null): Financeiro
    {
        $financeiro->update([
            'status' => 'pago',
            'data_pagamento' => now(),
            'forma_pagamento' => $formaPagamento ?? $financeiro->forma_pagamento,
            'valor_pago' => $financeiro->valor,
        ]);

        return $financeiro->fresh();
    }
}
