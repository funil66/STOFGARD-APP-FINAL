<?php

namespace App\Filament\Resources\ListaDesejoResource\Pages;

use App\Filament\Resources\ListaDesejoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewListaDesejo extends ViewRecord
{
    protected static string $resource = ListaDesejoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
