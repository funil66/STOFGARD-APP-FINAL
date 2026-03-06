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

        // Monitoramento de Trial e Limites (Novos Alertas de Ação)
        $trialsVencendo = Tenant::where('status_pagamento', 'trial')
            ->whereNotNull('trial_termina_em')
            ->whereDate('trial_termina_em', '<=', now()->addDays(3))
            ->whereDate('trial_termina_em', '>=', now())
            ->count();

        // Contando tenants que estão com mais de 80% do limite de OS ou que excederam
        $tenantsPertoLimite = Tenant::where('limite_os_mes', '>', 0)
            ->whereRaw('os_criadas_mes_atual >= (limite_os_mes * 0.8)')
            ->count();

        return [
            Stat::make('MRR (Receita Recorrente)', 'R$ ' . number_format($mrr, 2, ',', '.'))
                ->description('Faturamento mensal projetado')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary')
                ->chart([7, 10, 15, 20, 25, $mrr]), // Graficozinho legal

            Stat::make('Total de Clientes (SaaS)', $totalTenants)
                ->description('Todas as contas criadas')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Clientes Pagantes (Ativos)', $ativos)
                ->description('Livre de caloteiros')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Trials na Zona de Risco', $trialsVencendo)
                ->description('Vencem em até 3 dias. Chame-os no zap!')
                ->descriptionIcon('heroicon-m-fire')
                ->color($trialsVencendo > 0 ? 'warning' : 'gray'),

            Stat::make('Tenants Perto do Teto', $tenantsPertoLimite)
                ->description('Atingiram 80%+ do limite de OS')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($tenantsPertoLimite > 0 ? 'danger' : 'gray'),
        ];
    }
}
