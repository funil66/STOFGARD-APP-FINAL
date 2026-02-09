<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RelatorioFinanceiroChart;
use App\Filament\Widgets\VendasPorServicoChart;
use Filament\Pages\Page;

class RelatoriosAvancados extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.relatorios-avancados';

    protected static ?string $title = 'Relatórios Gerenciais';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;

    protected function getHeaderWidgets(): array
    {
        return [
            RelatorioFinanceiroChart::class,
            VendasPorServicoChart::class,
        ];
    }
}
