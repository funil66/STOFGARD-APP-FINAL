<?php

namespace App\Filament\Resources\ConfiguracaoResource\Pages;

use App\Filament\Resources\ConfiguracaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConfiguracaos extends ListRecords
{
    protected static string $resource = ConfiguracaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
