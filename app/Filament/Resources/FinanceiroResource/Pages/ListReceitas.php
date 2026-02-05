<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListReceitas extends ListRecords
{
    protected static string $resource = FinanceiroResource::class;

    protected static ?string $title = '💰 Receitas do Mês';

    protected static ?string $navigationLabel = 'Receitas';

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('tipo', 'entrada')
            ->whereMonth('data', now()->month)
            ->whereYear('data', now()->year)
            ->orderBy('data', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\FinanceiroOverview::class,
        ];
    }
}
