<?php

namespace App\Filament\Cliente\Resources\OrcamentoResource\Pages;

use App\Filament\Cliente\Resources\OrcamentoResource;
use Filament\Resources\Pages\ListRecords;

class ListOrcamentos extends ListRecords
{
    protected static string $resource = OrcamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
