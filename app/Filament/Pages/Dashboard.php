<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            // Seus cards de topo (R$ Saldo, etc)
            \App\Filament\Widgets\FinanceiroStats::class,

            // O Grid de 9 Ícones
            \App\Filament\Widgets\DashboardShortcutsWidget::class,

            // Gráficos (opcional, se quiser remover, comente a linha abaixo)
            \App\Filament\Widgets\MetricasGeraisWidget::class,
        ];
    }
}
