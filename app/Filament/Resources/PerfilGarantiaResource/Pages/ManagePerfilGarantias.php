<?php

namespace App\Filament\Resources\PerfilGarantiaResource\Pages;

use App\Filament\Resources\PerfilGarantiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePerfilGarantias extends ManageRecords
{
    protected static string $resource = PerfilGarantiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
