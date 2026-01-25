<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Resources\CadastroResource;
use App\Filament\Resources\OrdemServicoResource;
use App\Filament\Resources\AgendaResource;
use App\Filament\Resources\OrcamentoResource;
use App\Filament\Resources\FinanceiroResource;
use App\Filament\Resources\EstoqueResource;
use App\Filament\Resources\ConfiguracaoResource;
use App\Filament\Pages\BuscaAvancada;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel Principal';

    // Define explicitamente a view personalizada que criamos
    protected static string $view = 'filament.pages.dashboard';

    // Header widgets (atalhos e indicadores)
    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AtalhosRapidos::class,
            \Filament\Widgets\StatsOverviewWidget::class,
        ];
    }

    // Injeta os dados dos cards na view
    protected function getViewData(): array
    {
        return [
            'modules' => [
                [
                    'name' => 'Clientes',
                    'route' => CadastroResource::getUrl(),
                    'icon' => 'heroicon-o-users',
                    'color' => '#3b82f6', // blue
                ],
                [
                    'name' => 'Parceiros',
                    'route' => CadastroResource::getUrl(),
                    'icon' => 'heroicon-o-briefcase',
                    'color' => '#6366f1', // indigo
                ],
                [
                    'name' => 'Ordens de Serviço',
                    'route' => OrdemServicoResource::getUrl(),
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'color' => '#f97316', // orange
                ],
                [
                    'name' => 'Agenda',
                    'route' => AgendaResource::getUrl(),
                    'icon' => 'heroicon-o-calendar',
                    'color' => '#8b5cf6', // violet
                ],
                [
                    'name' => 'Financeiro',
                    'route' => \App\Filament\Resources\TransacaoFinanceiraResource::getUrl(),
                    'icon' => 'heroicon-o-currency-dollar',
                    'color' => '#22c55e', // green
                ],
                [
                    'name' => 'Orçamentos',
                    'route' => OrcamentoResource::getUrl(),
                    'icon' => 'heroicon-o-banknotes',
                    'color' => '#059669', // green
                ],
                [
                    'name' => 'Almoxarifado',
                    'route' => EstoqueResource::getUrl(),
                    'icon' => 'heroicon-o-cube',
                    'color' => '#db2777', // pink
                ],
                [
                    'name' => 'Configurações',
                    'route' => ConfiguracaoResource::getUrl('edit', ['record' => 1]),
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'color' => '#64748b', // gray
                ],
                [
                    'name' => 'Busca Avançada',
                    'route' => BuscaAvancada::getUrl(),
                    'icon' => 'heroicon-o-magnifying-glass-circle',
                    'color' => '#db2777', // pink
                ],
            ],
        ];
    }
}
