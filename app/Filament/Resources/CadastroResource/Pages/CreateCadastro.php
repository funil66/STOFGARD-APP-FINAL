<?php

namespace App\Filament\Resources\CadastroResource\Pages;

use App\Filament\Resources\CadastroResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCadastro extends CreateRecord
{
    protected static string $resource = CadastroResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If creating a Parceiro (loja/vendedor), set registrado_por initials
        $tipo = $data['tipo_cadastro'] ?? 'cliente';

        if (in_array($tipo, ['loja', 'vendedor'])) {
            $data['tipo'] = $tipo;
            // Set registrador initials like other parceiro flows
            $data['registrado_por'] = strtoupper(substr(auth()->user()->name ?? 'NA', 0, 2));
        }

        // Clean helper key before creating
        unset($data['tipo_cadastro']);

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Decide para qual model salvar conforme tipo (default cliente)
        $tipo = $data['tipo'] ?? 'cliente';

        if ($tipo === 'cliente') {
            return \App\Models\Cliente::create($data);
        }

        if (in_array($tipo, ['loja', 'vendedor'])) {
            return \App\Models\Parceiro::create($data);
        }

        throw new \Exception('Tipo de cadastro inválido.');
    }

    protected function getRedirectUrl(): string
    {
        // After creating, redirect to the proper resource view (cliente or parceiro)
        $model = $this->record;

        if ($model instanceof \App\Models\Cliente) {
            return $this->getResource()::getUrl('view', ['record' => $model]);
        }

        if ($model instanceof \App\Models\Parceiro) {
            return \App\Filament\Resources\ParceiroResource::getUrl('view', ['record' => $model]);
        }

        return parent::getRedirectUrl();
    }
}
