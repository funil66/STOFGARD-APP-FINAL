<?php

namespace App\Filament\Resources\TransacaoFinanceiraResource\Pages;

use App\Filament\Resources\TransacaoFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransacaoFinanceira extends EditRecord
{
    protected static string $resource = TransacaoFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
