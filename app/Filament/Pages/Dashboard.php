<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Sobrescreve a descoberta automática e define manualmente
    public function getWidgets(): array
    {
        return [
            // 1. Faixa Azul (Widget de Boas-vindas/Tempo)
            \App\Filament\Widgets\WeatherWidget::class,

            // 2. Os 9 Botões de Acesso Rápido (abaixo)
            \App\Filament\Widgets\DashboardShortcutsWidget::class,
        ];
    }

    // Garante que nenhuma coluna extra seja criada para widgets fantasmas
    public function getColumns(): int | string | array
    {
        return 1;
    }
}
