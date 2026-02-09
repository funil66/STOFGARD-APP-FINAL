<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget de estatísticas de comissões
 *
 * Exibe KPIs relacionados a comissões pendentes e pagas.
 */
class ComissoesStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendentes = Financeiro::where('is_comissao', true)
            ->where('comissao_paga', false)
            ->sum('valor');

        $pagas = Financeiro::where('is_comissao', true)
            ->where('comissao_paga', true)
            ->sum('valor');

        $pagasMes = Financeiro::where('is_comissao', true)
            ->where('comissao_paga', true)
            ->whereMonth('comissao_data_pagamento', now()->month)
            ->whereYear('comissao_data_pagamento', now()->year)
            ->sum('valor');

        $qtdPendentes = Financeiro::where('is_comissao', true)
            ->where('comissao_paga', false)
            ->count();

        return [
            Stat::make('⏳ Pendentes', 'R$ '.number_format($pendentes, 2, ',', '.'))
                ->description("$qtdPendentes comissões aguardando")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('✅ Pagas (Total)', 'R$ '.number_format($pagas, 2, ',', '.'))
                ->description('Todas as comissões pagas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('📅 Pagas (Mês)', 'R$ '.number_format($pagasMes, 2, ',', '.'))
                ->description('Comissões pagas este mês')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
