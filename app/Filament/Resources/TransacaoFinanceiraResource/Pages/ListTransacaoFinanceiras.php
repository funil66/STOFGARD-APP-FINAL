<?php

namespace App\Filament\Resources\TransacaoFinanceiraResource\Pages;

use App\Filament\Resources\TransacaoFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransacaoFinanceiras extends ListRecords
{
    protected static string $resource = TransacaoFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
