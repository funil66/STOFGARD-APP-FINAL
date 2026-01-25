<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            // Mantém o widget de Clima/Boas-vindas
            \App\Filament\Widgets\DashboardWeatherWidget::class,

            // Os 9 Ícones de Acesso Rápido
            \App\Filament\Widgets\DashboardShortcutsWidget::class,
        ];
    }
}
