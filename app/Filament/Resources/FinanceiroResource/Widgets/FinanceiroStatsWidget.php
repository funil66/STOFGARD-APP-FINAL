<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceiroStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();

        $entradas = Financeiro::where('tipo', 'entrada')
            ->where('data', '>=', $thisMonth)
            ->sum('valor');

        $saidas = Financeiro::where('tipo', 'saida')
            ->where('data', '>=', $thisMonth)
            ->sum('valor');

        $saldo = Financeiro::where('status', 'pago')
            ->where('data', '>=', $thisMonth)
            ->get()
            ->sum(function ($record) {
                return $record->tipo === 'entrada' ? $record->valor : -$record->valor;
            });

        $pendentes = Financeiro::where('status', 'pendente')
            ->sum('valor');

        $atrasados = Financeiro::where('status', 'atrasado')
            ->sum('valor');

        return [
            Stat::make('Saldo em Caixa', 'R$ ' . number_format($saldo, 2, ',', '.'))
                ->description('Total acumulado (Entradas - Saídas)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldo >= 0 ? 'success' : 'danger')
                ->url(route('filament.admin.resources.financeiros.transacoes.extratos')),

            Stat::make('Receitas (Mês)', 'R$ ' . number_format($entradas, 2, ',', '.'))
                ->description('Vencimento este mês')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(route('filament.admin.resources.financeiros.transacoes.receitas')),

            Stat::make('Despesas (Mês)', 'R$ ' . number_format($saidas, 2, ',', '.'))
                ->description('Vencimento este mês')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->url(route('filament.admin.resources.financeiros.transacoes.despesas')),

            Stat::make('Pendentes a Receber', 'R$ ' . number_format($pendentes, 2, ',', '.'))
                ->description('Aguardando baixa')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.financeiros.transacoes.pendentes')),
        ];
    }
}
