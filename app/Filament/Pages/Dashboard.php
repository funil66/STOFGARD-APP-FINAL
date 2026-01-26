<?php
namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard { // Força o Dashboard a ter apenas 1 coluna larga public function getColumns(): int | string | array { return 1; }

public function getWidgets(): array
{
    return [
        // 1. Faixa Azul (Topo)
        \App\Filament\Widgets\WeatherWidget::class,
        
        // 2. Atalhos (Grid de 9 Ícones)
        \App\Filament\Widgets\DashboardShortcutsWidget::class,
    ];
}
}

