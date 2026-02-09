<?php

namespace App\Services;

use App\Models\Financeiro;
use App\Models\OrdensServico;
use Carbon\Carbon;

class ProLaboreCalculator
{
    /**
     * Calcula o lucro líquido de um período
     */
    public function calcularLucroLiquido(Carbon $inicio, Carbon $fim): float
    {
        $receitas = Financeiro::where('tipo', 'entrada')
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->sum('valor_pago');

        $despesas = Financeiro::where('tipo', 'saida')
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->sum('valor_pago');

        return $receitas - $despesas;
    }

    /**
     * Calcula o valor de reserva (estoque/caixa)
     */
    public function calcularReserva(float $lucro, float $percentual): float
    {
        if ($lucro <= 0)
            return 0;
        return $lucro * ($percentual / 100);
    }

    /**
     * Calcula a distribuição para os sócios
     */
    public function calcularDistribuicao(float $lucroDisponivel, array $socios): array
    {
        // $socios = [['nome' => 'João', 'percentual' => 50], ...]
        $distribuicao = [];

        foreach ($socios as $socio) {
            $valor = $lucroDisponivel * ($socio['percentual'] / 100);
            $distribuicao[] = [
                'nome' => $socio['nome'],
                'percentual' => $socio['percentual'],
                'valor' => $valor
            ];
        }

        return $distribuicao;
    }
}
