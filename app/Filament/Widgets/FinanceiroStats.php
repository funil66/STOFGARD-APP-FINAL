<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\TransacaoFinanceira;
use Illuminate\Support\Facades\DB;

class FinanceiroStats extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();

        $saldoMes = TransacaoFinanceira::where('status', 'pago')
            ->where('data_pagamento', '>=', $currentMonth)
            ->selectRaw("SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as saldo")
            ->value('saldo') ?? 0;

        $aReceber = TransacaoFinanceira::where('status', 'pendente')
            ->where('tipo', 'receita')
            ->sum('valor_previsto');

        $aPagar = TransacaoFinanceira::where('status', 'pendente')
            ->where('tipo', 'despesa')
            ->sum('valor_previsto');

        return [
            Stat::make('Saldo Mês', 'R$ ' . number_format($saldoMes, 2, ',', '.'))
                ->color($saldoMes >= 0 ? 'success' : 'danger'),

            Stat::make('A Receber', 'R$ ' . number_format($aReceber, 2, ',', '.'))
                ->icon('heroicon-m-arrow-trending-up')
                ->color('warning'),

            Stat::make('A Pagar', 'R$ ' . number_format($aPagar, 2, ',', '.'))
                ->icon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}