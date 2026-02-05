<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAtrasadas extends ListRecords
{
    protected static string $resource = FinanceiroResource::class;

    protected static ?string $title = '⚠️ Contas Atrasadas';

    protected static ?string $navigationLabel = 'Atrasadas';

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('status', 'atrasado')
            ->orderBy('data_vencimento', 'asc');
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
