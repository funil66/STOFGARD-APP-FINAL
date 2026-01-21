<?php

namespace App\Filament\Resources\ParceiroResource\Pages;

use App\Filament\Resources\ParceiroResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateParceiro extends CreateRecord
{
    protected static string $resource = ParceiroResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['registrado_por'] = strtoupper(substr(Auth::user()->name, 0, 2));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
