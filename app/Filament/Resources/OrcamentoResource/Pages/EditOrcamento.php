<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOrcamento extends EditRecord
{
    protected static string $resource = OrcamentoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Registrar usuário que atualizou
        $user = Auth::user();
        $data['atualizado_por'] = strtoupper(substr($user->name, 0, 2));

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

        // Recalcular valores
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
}
