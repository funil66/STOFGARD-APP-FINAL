<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
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
