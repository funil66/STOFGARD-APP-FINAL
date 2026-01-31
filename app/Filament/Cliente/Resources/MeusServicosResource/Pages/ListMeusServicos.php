<?php

namespace App\Filament\Cliente\Resources\MeusServicosResource\Pages;

use App\Filament\Cliente\Resources\MeusServicosResource;
use Filament\Resources\Pages\ListRecords;

class ListMeusServicos extends ListRecords
{
    protected static string $resource = MeusServicosResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
