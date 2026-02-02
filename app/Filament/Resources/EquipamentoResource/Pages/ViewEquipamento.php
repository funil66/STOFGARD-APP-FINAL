<?php

namespace App\Filament\Resources\EquipamentoResource\Pages;

use App\Filament\Resources\EquipamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipamento extends ViewRecord
{
    protected static string $resource = EquipamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
