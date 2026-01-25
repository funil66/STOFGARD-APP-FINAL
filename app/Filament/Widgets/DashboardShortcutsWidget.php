<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardShortcutsWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-shortcuts-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    /**
     * Define os módulos principais seguindo o layout original do dashboard
     */
    public function getModules(): array
    {
        // Use the global helper which is robust to missing named routes and exceptions
        $s = function ($name, $fallback, $params = []) {
            return admin_resource_route($name, $fallback, $params);
        };

        try {
            return [
                [
                    'name' => 'Clientes',
                    'route' => url('/admin/cadastros'),
                    'icon' => 'heroicon-o-user-group',
                    'icon_background' => '#3B82F6',
                ],
                [
                    'name' => 'Ordens de Serviço',
                    'route' => $s('filament.admin.resources.ordem-servicos.index', '/admin/ordem-servicos'),
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'icon_background' => '#F97316',
                ],
                [
                    'name' => 'Agenda',
                    'route' => $s('filament.admin.resources.agendas.index', '/admin/agendas'),
                    'icon' => 'heroicon-o-calendar-days',
                    'icon_background' => '#A855F7',
                ],
                [
                    'name' => 'Orçamentos',
                    'route' => $s('filament.admin.resources.orcamentos.index', '/admin/orcamentos'),
                    'icon' => 'heroicon-o-calculator',
                    'icon_background' => '#10B981',
                ],

                // Produtos (restaurando 9 atalhos no dashboard)
                [
                    'name' => 'Produtos',
                    'route' => $s('filament.admin.resources.produtos.index', '/admin/produtos'),
                    'icon' => 'heroicon-o-box',
                    'icon_background' => '#F59E0B',
                ],

                [
                    'name' => 'Financeiro',
                    'route' => $s('filament.admin.resources.transacao-financeiras.index', '/admin/financeiros'),
                    'icon' => 'heroicon-o-banknotes',
                    'icon_background' => '#059669',
                    'description' => 'Receitas, despesas e notas',
                    'submodules' => [
                        ['name' => 'Visão Geral', 'route' => $s('filament.admin.resources.transacao-financeiras.index', '/admin/financeiros'), 'icon' => 'heroicon-o-banknotes'],
                        ['name' => 'Registros Financeiros', 'route' => $s('filament.admin.resources.financeiros.index', '/admin/financeiros/regs'), 'icon' => 'heroicon-o-clipboard-document-check'],
                        ['name' => 'Notas Fiscais', 'route' => $s('filament.admin.resources.nota-fiscals.index', '/admin/notas-fiscais'), 'icon' => 'heroicon-o-document-text'],
                    ],
                ],

                [
                    'name' => 'Parceiros',
                    'route' => url('/admin/cadastros'),
                    'icon' => 'heroicon-o-building-office',
                    'icon_background' => '#6366F1',
                ],
                [
                    'name' => 'Configurações',
                    'route' => $s('filament.admin.pages.configuracoes-gerais', '/admin/configuracoes-gerais'),
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'icon_background' => '#6B7280',
                    'description' => 'Preferências do painel',
                    'submodules' => [
                        ['name' => 'Configurações Gerais', 'route' => $s('filament.admin.pages.configuracoes-gerais', '/admin/configuracoes-gerais'), 'icon' => 'heroicon-o-cog-6-tooth'],
                        ['name' => 'Configurações Avançadas', 'route' => $s('filament.admin.resources.configuracaos.index', '/admin/configuracoes'), 'icon' => 'heroicon-o-wrench-screwdriver'],
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return [
                ['name' => 'Cadastros', 'route' => url('/admin/cadastros'), 'icon' => 'heroicon-o-user-group', 'icon_background' => '#3B82F6'],
            ];
        }
    }
}
