<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardShortcutsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    // Garante que o layout ocupe a tela toda (bom para o seu Monitor Ultrawide)
    public function getColumns(): int|string|array
    {
        return 'full';
    }

    public function getViewData(): array
    {
        return [
            'modules' => [
                ['name' => 'Agenda', 'route' => url('/admin/agendas'), 'icon' => 'heroicon-o-calendar-days', 'color' => '#2563eb'],
                ['name' => 'Cadastros', 'route' => url('/admin/cadastros'), 'icon' => 'heroicon-o-users', 'color' => '#0ea5e9'],
                ['name' => 'Orçamentos', 'route' => url('/admin/orcamentos'), 'icon' => 'heroicon-o-document-text', 'color' => '#f59e0b'],
                ['name' => 'Ordens de Serviço', 'route' => url('/admin/ordem-servicos'), 'icon' => 'heroicon-o-wrench-screwdriver', 'color' => '#ef4444'],
                ['name' => 'Financeiro', 'route' => url('/admin/financeiros'), 'icon' => 'heroicon-o-banknotes', 'color' => '#10b981'],
                ['name' => 'Busca Avançada', 'route' => url('/admin/busca-avancada'), 'icon' => 'heroicon-o-magnifying-glass', 'color' => '#0f766e'],
                ['name' => 'Relatórios', 'route' => url('/admin/relatorios'), 'icon' => 'heroicon-o-chart-bar', 'color' => '#8b5cf6'],
                ['name' => 'Configurações Gerais', 'route' => url('/admin/configuracoes'), 'icon' => 'heroicon-o-cog-6-tooth', 'color' => '#64748b'],
            ],
        ];
    }

    /**
     * Define EXPLICITAMENTE quais widgets aparecem aqui.
     * Removemos todo o lixo, mantendo apenas o nosso Híbrido.
     */
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            DashboardShortcutsWidget::class,
        ];
    }
}
