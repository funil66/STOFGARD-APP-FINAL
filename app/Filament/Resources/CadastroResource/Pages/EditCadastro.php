<?php

namespace App\Filament\Resources\CadastroResource\Pages;

use App\Filament\Resources\CadastroResource;
use Filament\Resources\Pages\EditRecord;

class EditCadastro extends EditRecord
{
    protected static string $resource = CadastroResource::class;

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Decide para qual model atualizar conforme tipo_cadastro (se fornecido)
        $tipo = $data['tipo_cadastro'] ?? null;

        // Caso padrão (sem mudança de tipo ou explicitamente 'cliente')
        if ($tipo === null || $tipo === 'cliente') {
            unset($data['tipo_cadastro']);
            $record->update($data);
            return $record;
        }

        // Atualizar apenas se já for um Parceiro; não suportamos migrar Cliente->Parceiro via edição
        if (in_array($tipo, ['loja', 'vendedor'])) {
            if ($record instanceof \App\Models\Parceiro) {
                $data['tipo'] = $tipo;
                unset($data['tipo_cadastro']);
                $record->update($data);
                return $record;
            }

            throw new \Exception('Mudança de tipo de cadastro de Cliente para Parceiro não é suportada via edição. Crie um novo registro como Loja/Vendedor.');
        }

        throw new \Exception('Tipo de cadastro inválido.');
    }

    protected function getRedirectUrl(): string
    {
        // After editing, redirect to the resource view to close the modal / indicate success
        $model = $this->record;

        if ($model instanceof \App\Models\Cliente) {
            return $this->getResource()::getUrl('view', ['record' => $model]);
        }

        if ($model instanceof \App\Models\Parceiro) {
            return \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $model]);
        }

        return parent::getRedirectUrl();
    }
}
