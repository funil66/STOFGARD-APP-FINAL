<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardShortcutsWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-shortcuts-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function getShortcuts(): array
    {
        return [
            [
                'label' => 'Novo Cadastro',
                'icon' => 'heroicon-o-user-plus',
                'url' => '/admin/cadastros/create',
                'color' => 'bg-blue-600',
            ],
            [
                'label' => 'Novo Orçamento',
                'icon' => 'heroicon-o-document-plus',
                'url' => '/admin/orcamentos/create',
                'color' => 'bg-green-600',
            ],
            [
                'label' => 'Gestão Financeira',
                'icon' => 'heroicon-o-banknotes',
                'url' => '/admin/financeiros',
                'color' => 'bg-emerald-600',
            ],
            [
                'label' => 'Meus Clientes',
                'icon' => 'heroicon-o-users',
                'url' => '/admin/cadastros?activeTab=clientes',
                'color' => 'bg-indigo-600',
            ],
            [
                'label' => 'Parceiros e Lojas',
                'icon' => 'heroicon-o-briefcase',
                'url' => '/admin/cadastros?activeTab=parceiros',
                'color' => 'bg-purple-600',
            ],
            [
                'label' => 'Orçamentos',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => '/admin/orcamentos',
                'color' => 'bg-orange-500',
            ],
            [
                'label' => 'Ordens de Serviço',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'url' => '/admin/ordens-servico',
                'color' => 'bg-red-600',
            ],
            [
                'label' => 'Relatórios',
                'icon' => 'heroicon-o-chart-bar',
                'url' => '/admin',
                'color' => 'bg-gray-600',
            ],
            [
                'label' => 'Configurações',
                'icon' => 'heroicon-o-cog-6-tooth',
                'url' => '/admin',
                'color' => 'bg-slate-700',
            ],
        ];
    }
}

