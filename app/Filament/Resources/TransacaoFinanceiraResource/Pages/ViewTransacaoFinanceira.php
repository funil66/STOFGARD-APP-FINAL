<?php

namespace App\Filament\Resources\TransacaoFinanceiraResource\Pages;

use App\Filament\Resources\TransacaoFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransacaoFinanceira extends ViewRecord
{
    protected static string $resource = TransacaoFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
