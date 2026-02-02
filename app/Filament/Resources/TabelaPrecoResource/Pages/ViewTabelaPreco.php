<?php

namespace App\Filament\Resources\TabelaPrecoResource\Pages;

use App\Filament\Resources\TabelaPrecoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTabelaPreco extends ViewRecord
{
    protected static string $resource = TabelaPrecoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
