<?php

namespace App\Filament\Resources\ContratoRecorrenteResource\Pages;

use App\Filament\Resources\ContratoRecorrenteResource;
use App\Services\AsaasService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateContratoRecorrente extends CreateRecord
{
    protected static string $resource = ContratoRecorrenteResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\ContratoRecorrente $record */
        $record = $this->record;

        try {
            $asaasService = app(AsaasService::class);
            
            // Busca dados do cliente para a integração
            $cliente = $record->cliente;
            
            // Estrutura os dados para a orquestração via service
            $dadosCliente = [
                'id' => $cliente->id,
                'name' => $cliente->nome,
                'email' => $cliente->email,
                'cpf_cnpj' => $cliente->cpf_cnpj ?? $cliente->cpf ?? null,
                'phone' => $cliente->telefone ?? null,
                'tenant_id' => "CLIENTE_AUTONOMIA_{$cliente->id}", // Garante ref externa única
                'billingType' => 'PIX',
                'descricao' => "Contrato Recorrente - Ciclo: {$record->ciclo}",
            ];

            // Injeta a chamada do gateway Asaas
            $respostaAsaas = $asaasService->createSubscription(
                $dadosCliente,
                (float) $record->valor,
                $record->ciclo
            );

            // Atualiza o registro salvando a Subscription ID para os webhooks
            $record->update([
                'gateway_subscription_id' => $respostaAsaas['id'] ?? null,
            ]);

            Notification::make()
                ->title('Sucesso!')
                ->body('Contrato recorrente sincronizado com o gateway Asaas.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro no Gateway de Pagamento')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
