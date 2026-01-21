<?php

namespace App\Filament\Resources\ParceiroResource\Pages;

use App\Filament\Resources\ParceiroResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewParceiro extends ViewRecord
{
    protected static string $resource = ParceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
