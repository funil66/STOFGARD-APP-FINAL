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
            [
                'label' => 'Novo Cadastro',
                'icon' => 'heroicon-o-user-plus',
                'url' => route('filament.admin.resources.cadastros.create'),
                'color' => '#2563EB', // Azul Forte
            ],
            [
                'label' => 'Novo Orçamento',
                'icon' => 'heroicon-o-document-plus',
                'url' => route('filament.admin.resources.orcamentos.create'),
                'color' => '#16A34A', // Verde
            ],
            [
                'label' => 'Gestão Financeira',
                'icon' => 'heroicon-o-banknotes',
                'url' => '/admin/financeiros',
                'color' => '#059669', // Esmeralda
            ],
            [
                'label' => 'Meus Clientes',
                'icon' => 'heroicon-o-users',
                'url' => '/admin/cadastros?activeTab=clientes',
                'color' => '#4F46E5', // Indigo
            ],
            [
                'label' => 'Parceiros e Lojas',
                'icon' => 'heroicon-o-briefcase',
                'url' => '/admin/cadastros?activeTab=parceiros',
                'color' => '#9333EA', // Roxo
            ],
            [
                'label' => 'Orçamentos',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => route('filament.admin.resources.orcamentos.index'),
                'color' => '#F97316', // Laranja
            ],
            [
                'label' => 'Ordens de Serviço',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'url' => '/admin/ordens-servico',
                'color' => '#DC2626', // Vermelho
            ],
            [
                'label' => 'Relatórios',
                'icon' => 'heroicon-o-chart-bar',
                'url' => '/admin', 
                'color' => '#4B5563', // Cinza Escuro
            ],
            [
                'label' => 'Configurações',
                'icon' => 'heroicon-o-cog-6-tooth',
                'url' => '/admin', 
                'color' => '#334155', // Slate
            ],
        ];
    }
}

