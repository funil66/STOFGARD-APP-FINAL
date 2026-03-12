<?php

namespace App\Filament\Resources\ContratoServicoResource\Pages;

use App\Filament\Resources\ContratoServicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContratosServico extends ListRecords
{
    protected static string $resource = ContratoServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo Contrato'),
        ];
    }
}
