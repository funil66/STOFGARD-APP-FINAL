<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tenant;

class SaaSMrrWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTenants = Tenant::count();
        $ativos = Tenant::where('status_pagamento', 'ativo')->orWhere('is_active', true)->count();

        // Simulação de cálculo de Faturamento Baseado nos Planos (Exemplo)
        $mrr = 0;
        $tenants = Tenant::where('is_active', true)->get();
        foreach ($tenants as $t) {
            $plano = strtolower($t->plan ?? 'free');
            if ($plano === 'start' || $plano === 'free')
                $mrr += 49.90;
            if ($plano === 'pro')
                $mrr += 97.90;
            if ($plano === 'elite')
                $mrr += 197.90;
        }

        return [
            Stat::make('Total de Clientes (SaaS)', $totalTenants)
                ->description('Todas as contas criadas')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Clientes Pagantes (Ativos)', $ativos)
                ->description('Livre de caloteiros')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('MRR (Receita Recorrente)', 'R$ ' . number_format($mrr, 2, ',', '.'))
                ->description('Faturamento mensal projetado')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary')
                ->chart([7, 10, 15, 20, 25, $mrr]), // Graficozinho legal
        ];
    }
}
