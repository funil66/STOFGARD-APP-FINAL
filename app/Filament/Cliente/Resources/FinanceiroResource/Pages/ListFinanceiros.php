<?php

namespace App\Filament\Cliente\Resources\FinanceiroResource\Pages;

use App\Filament\Cliente\Resources\FinanceiroResource;
use Filament\Resources\Pages\ListRecords;

class ListFinanceiros extends ListRecords
{
    protected static string $resource = FinanceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
