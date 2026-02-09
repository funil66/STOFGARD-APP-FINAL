<?php

namespace App\Filament\Resources\EquipamentoResource\Pages;

use App\Filament\Resources\EquipamentoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipamento extends CreateRecord
{
    protected static string $resource = EquipamentoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['criado_por'] = strtoupper(substr(auth()->user()->name ?? 'SYS', 0, 10));

        return $data;
    }
}
