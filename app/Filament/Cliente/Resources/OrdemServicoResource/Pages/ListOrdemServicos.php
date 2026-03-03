<?php

namespace App\Filament\Cliente\Resources\OrdemServicoResource\Pages;

use App\Filament\Cliente\Resources\OrdemServicoResource;
use Filament\Resources\Pages\ListRecords;

class ListOrdemServicos extends ListRecords
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
