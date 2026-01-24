<?php

namespace App\Filament\Resources\ListaDesejoResource\Pages;

use App\Filament\Resources\ListaDesejoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListaDesejos extends ListRecords
{
    protected static string $resource = ListaDesejoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
