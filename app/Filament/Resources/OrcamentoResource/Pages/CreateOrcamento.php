<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use App\Models\Orcamento;
use Filament\Resources\Pages\CreateRecord;

class CreateOrcamento extends CreateRecord
{
    protected static string $resource = OrcamentoResource::class;

    // Intercepta os dados antes de criar para injetar o número
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Gera o número sequencial (Ex: 2026.0001)
        $data['numero'] = Orcamento::gerarNumeroOrcamento();

        // CORREÇÃO DO ERRO 1364: Define a data de emissão como HOJE se não vier do form
        if (! isset($data['data_orcamento'])) {
            $data['data_orcamento'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
