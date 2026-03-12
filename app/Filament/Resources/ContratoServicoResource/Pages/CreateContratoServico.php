<?php

namespace App\Filament\Resources\ContratoServicoResource\Pages;

use App\Filament\Resources\ContratoServicoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContratoServico extends CreateRecord
{
    protected static string $resource = ContratoServicoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-fill proximo_agendamento if not set
        if (empty($data['proximo_agendamento'])) {
            $data['proximo_agendamento'] = $data['data_inicio'];
        }

        return $data;
    }
}
