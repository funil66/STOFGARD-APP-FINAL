<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Filament\Pages\Relatorios;
use App\Filament\Pages\RelatoriosAvancados;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinanceiros extends ListRecords
{
    protected static string $resource = FinanceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Transação')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('categorias')
                ->label('Categorias')
                ->icon('heroicon-o-tag')
                ->color('info')
                ->url(url('/admin/categorias')),

            Actions\Action::make('notas_fiscais')
                ->label('Notas Fiscais')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('info')
                ->url(url('/admin/notas-fiscais')),

            Actions\ActionGroup::make([
                Actions\Action::make('relatorioSimples')
                    ->label('Relatórios')
                    ->icon('heroicon-o-chart-bar')
                    ->url(Relatorios::getUrl()),
                Actions\Action::make('relatorioAvancado')
                    ->label('Relatórios Gerenciais')
                    ->icon('heroicon-o-chart-bar-square')
                    ->url(RelatoriosAvancados::getUrl()),
            ])
            ->label('Relatórios')
            ->icon('heroicon-m-document-chart-bar')
            ->color('success')
            ->button(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\FinanceiroOverview::class,
            FinanceiroResource\Widgets\FluxoCaixaChart::class,
            FinanceiroResource\Widgets\DespesasCategoriaChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
