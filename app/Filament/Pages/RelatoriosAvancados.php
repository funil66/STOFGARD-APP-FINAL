<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\RelatorioFinanceiroChart;
use App\Filament\Widgets\VendasPorServicoChart;

class RelatoriosAvancados extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.relatorios-avancados';

    protected static ?string $title = 'Relatórios Gerenciais';

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 5;

    protected function getHeaderWidgets(): array
    {
        return [
            RelatorioFinanceiroChart::class,
            VendasPorServicoChart::class,
        ];
    }
}
