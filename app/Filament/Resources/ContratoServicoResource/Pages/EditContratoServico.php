<?php

namespace App\Filament\Resources\ContratoServicoResource\Pages;

use App\Filament\Resources\ContratoServicoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContratoServico extends EditRecord
{
    protected static string $resource = ContratoServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
