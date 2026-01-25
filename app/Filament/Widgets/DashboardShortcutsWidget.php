<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardShortcutsWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-shortcuts-widget';

    // Define a ordem para aparecer no topo
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public function getShortcuts(): array
    {
        return [
            [
                'label' => 'Novo Cadastro',
                'icon' => 'heroicon-o-user-plus',
                'url' => route('filament.admin.resources.cadastros.create'),
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Novo Orçamento',
                'icon' => 'heroicon-o-document-plus',
                'url' => route('filament.admin.resources.orcamentos.create'),
                'color' => 'bg-green-500',
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
                'color' => 'bg-indigo-500',
            ],
            [
                'label' => 'Parceiros e Lojas',
                'icon' => 'heroicon-o-briefcase',
                'url' => '/admin/cadastros?activeTab=parceiros',
                'color' => 'bg-purple-500',
            ],
            [
                'label' => 'Orçamentos',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => route('filament.admin.resources.orcamentos.index'),
                'color' => 'bg-orange-500',
            ],
            [
                'label' => 'Ordens de Serviço',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'url' => '/admin/ordens-servico',
                'color' => 'bg-red-500',
            ],
            [
                'label' => 'Relatórios',
                'icon' => 'heroicon-o-chart-bar',
                'url' => '/admin',
                'color' => 'bg-gray-500',
            ],
            [
                'label' => 'Configurações',
                'icon' => 'heroicon-o-cog-6-tooth',
                'url' => '/admin',
                'color' => 'bg-slate-600',
            ],
        ];
    }
}
