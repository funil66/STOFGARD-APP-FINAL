<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Events\OrcamentoCriado;
use App\Filament\Resources\OrcamentoResource;
use App\Models\Orcamento;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOrcamento extends CreateRecord
{
    protected static string $resource = OrcamentoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Gerar número do orçamento
        $data['numero_orcamento'] = Orcamento::gerarNumeroOrcamento();

        // Definir status inicial
        $data['status'] = 'pendente';

        // Registrar usuário criador
        $user = Auth::user();
        $data['criado_por'] = strtoupper(substr($user->name, 0, 2));

        // Unificar cadastro: preservar cadastro_id e popular legacy
        if (isset($data['cadastro_id'])) {
            if (str_starts_with($data['cadastro_id'], 'cliente_')) {
                $data['cliente_id'] = (int)str_replace('cliente_', '', $data['cadastro_id']);
                $data['parceiro_id'] = null;
            } elseif (str_starts_with($data['cadastro_id'], 'parceiro_')) {
                $data['parceiro_id'] = (int)str_replace('parceiro_', '', $data['cadastro_id']);
                $data['cliente_id'] = null;
            }
        }

        // Calcular valores se necessário
        if (isset($data['area_m2']) && isset($data['valor_m2'])) {
            $data['valor_subtotal'] = $data['area_m2'] * $data['valor_m2'];

            if (isset($data['desconto_pix_aplicado']) && $data['desconto_pix_aplicado']) {
                $data['valor_desconto'] = $data['valor_subtotal'] * 0.10;
            } else {
                $data['valor_desconto'] = 0;
            }

            $data['valor_total'] = $data['valor_subtotal'] - $data['valor_desconto'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Disparar evento para automações WhatsApp
        OrcamentoCriado::dispatch($this->record);
    }
}
