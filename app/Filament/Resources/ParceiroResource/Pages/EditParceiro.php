<?php

namespace App\Filament\Resources\ParceiroResource\Pages;

use App\Filament\Resources\ParceiroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParceiro extends EditRecord
{
    protected static string $resource = ParceiroResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
