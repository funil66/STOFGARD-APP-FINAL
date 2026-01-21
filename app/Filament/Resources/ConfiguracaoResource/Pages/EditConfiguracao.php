<?php

namespace App\Filament\Resources\ConfiguracaoResource\Pages;

use App\Filament\Resources\ConfiguracaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConfiguracao extends EditRecord
{
    protected static string $resource = ConfiguracaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
