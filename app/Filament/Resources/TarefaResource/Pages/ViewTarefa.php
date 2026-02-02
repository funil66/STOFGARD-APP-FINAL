<?php

namespace App\Filament\Resources\TarefaResource\Pages;

use App\Filament\Resources\TarefaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTarefa extends ViewRecord
{
    protected static string $resource = TarefaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
