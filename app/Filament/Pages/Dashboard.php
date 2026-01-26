<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard { // OBRIGATÓRIO: Força 1 coluna para ocupar a tela toda public function getColumns(): int | string | array { return 1; }

public function getWidgets(): array
{
    return [
        \App\Filament\Widgets\WeatherWidget::class,
        \App\Filament\Widgets\DashboardShortcutsWidget::class,
    ];
}
}

