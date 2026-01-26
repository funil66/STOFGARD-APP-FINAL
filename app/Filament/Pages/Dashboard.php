<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard { // Sobrescreve a descoberta automática e define manualmente public function getWidgets(): array { return [ // 1. Apenas o Widget do Tempo/Boas-vindas (usando o nome correto)
            \App\Filament\Widgets\DashboardWeatherWidget::class,

            // 2. O Calendário (Se for um widget, inclua; se não, remova)
            // \App\Filament\Widgets\CalendarioWidget::class,

            // 3. Os 9 Botões de Acesso Rápido
            \App\Filament\Widgets\DashboardShortcutsWidget::class,
        ];
    }

    // Garante que nenhuma coluna extra seja criada para widgets fantasmas
    public function getColumns(): int | string | array
    {
        return 1;
    }
}
