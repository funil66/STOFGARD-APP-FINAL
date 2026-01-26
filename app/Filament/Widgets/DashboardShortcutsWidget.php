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
            ['label' => 'Busca Avançada', 'icon' => 'heroicon-o-magnifying-glass', 'url' => '/admin/busca-avancada', 'color' => '#0891B2'],
            ['label' => 'Cadastro', 'icon' => 'heroicon-o-user-group', 'url' => '/admin/cadastros', 'color' => '#2563EB'],
            ['label' => 'Ordem de Serviço', 'icon' => 'heroicon-o-wrench-screwdriver', 'url' => '/admin/ordem-servicos', 'color' => '#DC2626'],
            ['label' => 'Orçamento', 'icon' => 'heroicon-o-document-plus', 'url' => '/admin/orcamentos', 'color' => '#16A34A'],
            ['label' => 'Agenda', 'icon' => 'heroicon-o-calendar', 'url' => '/admin/agenda', 'color' => '#F59E0B'],
            ['label' => 'Financeiro', 'icon' => 'heroicon-o-banknotes', 'url' => '/admin/financeiros', 'color' => '#059669'],
            ['label' => 'Almoxarifado', 'icon' => 'heroicon-o-cube', 'url' => '/admin/produtos', 'color' => '#EA580C'],
            ['label' => 'Configurações', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => '/admin/configuracoes', 'color' => '#475569'],
        ];
    }
}

