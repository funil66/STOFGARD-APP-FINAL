<?php

namespace App\Filament\Resources\NotaFiscalResource\Pages;

use App\Filament\Resources\NotaFiscalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNotaFiscal extends ViewRecord
{
    protected static string $resource = NotaFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
