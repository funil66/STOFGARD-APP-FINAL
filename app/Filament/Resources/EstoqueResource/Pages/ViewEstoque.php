<?php

namespace App\Filament\Resources\EstoqueResource\Pages;

use App\Filament\Resources\EstoqueResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEstoque extends ViewRecord
{
    protected static string $resource = EstoqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
