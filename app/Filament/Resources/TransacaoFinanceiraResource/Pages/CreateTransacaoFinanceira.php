<?php

namespace App\Filament\Resources\TransacaoFinanceiraResource\Pages;

use App\Filament\Resources\TransacaoFinanceiraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransacaoFinanceira extends CreateRecord
{
    protected static string $resource = TransacaoFinanceiraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Define quem criou
        $data['criado_por'] = strtoupper(substr(auth()->user()->name ?? 'SYS', 0, 10));

        // Se status é pago mas não tem data de pagamento, usa a data da transação
        if ($data['status'] === 'pago' && empty($data['data_pagamento'])) {
            $data['data_pagamento'] = $data['data_transacao'];
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Transação financeira criada com sucesso!';
    }
}
