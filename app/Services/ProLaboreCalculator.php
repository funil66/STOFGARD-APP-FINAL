<?php

namespace App\Services;

use App\Models\Financeiro;
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
    public function calcularReserva(float $lucro): float
    {
        if ($lucro <= 0) {
            return 0;
        }

        $percentual = (float) settings()->get('prolabore_percentual_reserva', 20);

        return $lucro * ($percentual / 100);
    }

    /**
     * Calcula a distribuição para os sócios
     */
    public function calcularDistribuicao(float $lucroDisponivel): array
    {
        $sociosConfig = settings()->get('socios_config');

        // Se não houver configuração, retorna vazio
        if (empty($sociosConfig) || !is_array($sociosConfig)) {
            return [];
        }

        // Decodificar se vier como string (caso não tenha passado pelo cast do Settings)
        if (is_string($sociosConfig)) {
            $sociosConfig = json_decode($sociosConfig, true) ?? [];
        }

        $distribuicao = [];

        foreach ($sociosConfig as $socio) {
            $percentual = (float) ($socio['percentual'] ?? 0);
            $userId = $socio['user_id'] ?? null;

            if (!$userId)
                continue;

            $user = \App\Models\User::find($userId);

            $valor = $lucroDisponivel * ($percentual / 100);

            $distribuicao[] = [
                'user_id' => $userId,
                'nome' => $user?->name ?? 'Usuário Desconhecido',
                'percentual' => $percentual,
                'valor' => $valor,
            ];
        }

        return $distribuicao;
    }
}
