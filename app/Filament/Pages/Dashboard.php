<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int | string | array
    {
        return 1; // FORÇA LARGURA TOTAL
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\WeatherWidget::class,
            \App\Filament\Widgets\DashboardShortcutsWidget::class,
        ];
    }
}

