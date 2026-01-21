<?php

namespace App\Filament\Resources\TransacaoFinanceiraResource\Pages;

use App\Filament\Resources\TransacaoFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransacaoFinanceira extends EditRecord
{
    protected static string $resource = TransacaoFinanceiraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Define quem atualizou
        $data['atualizado_por'] = strtoupper(substr(auth()->user()->name ?? 'SYS', 0, 10));

        // Se mudou para pago mas não tem data de pagamento, usa hoje
        if ($data['status'] === 'pago' && empty($data['data_pagamento'])) {
            $data['data_pagamento'] = now();
        }

        // Verifica se está vencido
        if ($data['status'] === 'pendente' && ! empty($data['data_vencimento'])) {
            $vencimento = \Carbon\Carbon::parse($data['data_vencimento']);
            if ($vencimento->isPast()) {
                $data['status'] = 'vencido';
            }
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Transação financeira atualizada!';
    }
}
