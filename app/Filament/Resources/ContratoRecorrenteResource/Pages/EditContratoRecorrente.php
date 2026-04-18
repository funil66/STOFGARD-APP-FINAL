<?php

namespace App\Filament\Resources\ContratoRecorrenteResource\Pages;

use App\Filament\Resources\ContratoRecorrenteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContratoRecorrente extends EditRecord
{
    protected static string $resource = ContratoRecorrenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
