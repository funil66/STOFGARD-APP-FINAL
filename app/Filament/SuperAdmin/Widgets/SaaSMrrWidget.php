<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaaSMrrWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $ativos = Tenant::where('status_pagamento', 'ativo')->count();
        $trial = Tenant::where('status_pagamento', 'trial')->count();

        $mrrPro = Tenant::where('plan', 'pro')->where('status_pagamento', 'ativo')->count() * env('PLAN_PRO_PRICE', 97);
        $mrrElite = Tenant::where('plan', 'elite')->where('status_pagamento', 'ativo')->count() * env('PLAN_ELITE_PRICE', 197);

        $mrrTotal = $mrrPro + $mrrElite;

        return [
            Stat::make('MRR Estimado', 'R$ ' . number_format($mrrTotal, 2, ',', '.'))
                ->description('Planos PRO e ELITE Ativos')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Tenants Ativos', $ativos)
                ->description('Assinaturas em pagamento')
                ->color('primary')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Tenants em Trial', $trial)
                ->description('Testando gratuitamente')
                ->color('info')
                ->icon('heroicon-o-clock'),
        ];
    }
}
