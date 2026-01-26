<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardShortcutsWidget extends Widget {
    protected static string $view = 'filament.widgets.dashboard-shortcuts-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function getShortcuts(): array
    {
        return [
            ['label' => 'Clientes', 'icon' => 'heroicon-o-users', 'url' => '/admin/cadastros?activeTab=clientes', 'color' => '#2563EB'], // Azul
            ['label' => 'Parceiros', 'icon' => 'heroicon-o-briefcase', 'url' => '/admin/cadastros?activeTab=parceiros', 'color' => '#9333EA'], // Roxo
            ['label' => 'Orçamento', 'icon' => 'heroicon-o-document-plus', 'url' => '/admin/orcamentos', 'color' => '#16A34A'], // Verde
            ['label' => 'Financeiro', 'icon' => 'heroicon-o-banknotes', 'url' => '/admin/financeiros', 'color' => '#059669'], // Esmeralda
            ['label' => 'Agenda', 'icon' => 'heroicon-o-calendar', 'url' => '/admin/agenda', 'color' => '#F59E0B'], // Laranja
            ['label' => 'Almoxarifado', 'icon' => 'heroicon-o-cube', 'url' => '/admin/produtos', 'color' => '#EA580C'], // Laranja Escuro
            ['label' => 'Busca Avançada', 'icon' => 'heroicon-o-magnifying-glass', 'url' => '/admin/busca', 'color' => '#0891B2'], // Ciano
            ['label' => 'Configurações', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => '/admin', 'color' => '#475569'], // Slate
        ];
    }
}

