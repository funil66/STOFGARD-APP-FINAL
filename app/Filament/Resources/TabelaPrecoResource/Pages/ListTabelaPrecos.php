<?php

namespace App\Filament\Resources\TabelaPrecoResource\Pages;

use App\Filament\Resources\TabelaPrecoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTabelaPrecos extends ListRecords
{
    protected static string $resource = TabelaPrecoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
