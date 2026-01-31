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

        $saldo = $entradas - $saidas;

        $pendentes = Financeiro::where('status', 'pendente')
            ->sum('valor');

        $atrasados = Financeiro::where('status', 'atrasado')
            ->sum('valor');

        return [
            Stat::make('💰 Entradas (Mês)', 'R$ ' . number_format($entradas, 2, ',', '.'))
                ->description('Total de receitas no mês')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('📤 Saídas (Mês)', 'R$ ' . number_format($saidas, 2, ',', '.'))
                ->description('Total de despesas no mês')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('📊 Saldo do Mês', 'R$ ' . number_format($saldo, 2, ',', '.'))
                ->description($saldo >= 0 ? 'Positivo' : 'Negativo')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldo >= 0 ? 'success' : 'danger'),

            Stat::make('⏳ Contas Pendentes', 'R$ ' . number_format($pendentes, 2, ',', '.'))
                ->description('Aguardando pagamento')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('⚠️ Contas Atrasadas', 'R$ ' . number_format($atrasados, 2, ',', '.'))
                ->description('Vencidas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
