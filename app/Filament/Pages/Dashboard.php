<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\DashboardShortcutsWidget;

class Dashboard extends BaseDashboard
{
    // Garante que o layout ocupe a tela toda (bom para o seu Monitor Ultrawide)
    public function getColumns(): int | string | array
    {
        return 'full'; 
    }

    /**
     * Define EXPLICITAMENTE quais widgets aparecem aqui.
     * Removemos todo o lixo, mantendo apenas o nosso Híbrido.
     */
    public function getWidgets(): array
    {
        return [
            DashboardShortcutsWidget::class,
        ];
    }
}

