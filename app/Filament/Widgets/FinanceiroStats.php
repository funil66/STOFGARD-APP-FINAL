<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceiroStats extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();

        $saldoMes = Financeiro::where('status', 'pago')
            ->where('data_pagamento', '>=', $currentMonth)
            ->selectRaw("SUM(CASE WHEN tipo = 'entrada' THEN COALESCE(valor_pago, valor) ELSE -COALESCE(valor_pago, valor) END) as saldo")
            ->value('saldo') ?? 0;

        $aReceber = Financeiro::where('status', 'pendente')
            ->where('tipo', 'entrada')
            ->sum('valor');

        $aPagar = Financeiro::where('status', 'pendente')
            ->where('tipo', 'saida')
            ->sum('valor');

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
