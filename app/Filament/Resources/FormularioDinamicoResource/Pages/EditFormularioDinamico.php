<?php

namespace App\Filament\Resources\FormularioDinamicoResource\Pages;

use App\Filament\Resources\FormularioDinamicoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormularioDinamico extends EditRecord
{
    protected static string $resource = FormularioDinamicoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
