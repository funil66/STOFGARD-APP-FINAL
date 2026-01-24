<?php

namespace App\Filament\Resources\ListaDesejoResource\Pages;

use App\Filament\Resources\ListaDesejoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditListaDesejo extends EditRecord
{
    protected static string $resource = ListaDesejoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
