<?php

namespace App\Filament\Resources\ConfiguracaoResource\Pages;

use App\Filament\Resources\ConfiguracaoResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditConfiguracao extends EditRecord
{
    protected static string $resource = ConfiguracaoResource::class;

    // 1. Remove o "Pão de Migalhas" (Breadcrumbs) que tenta linkar para a listagem inexistente
    public function getBreadcrumbs(): array
    {
        return [];
    }

    // 2. Sobrescreve a ação de cancelar para não tentar voltar para o index
    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->url(null) // Remove o link
            ->hidden(); // Esconde o botão completamente
    }

    // 3. Ao salvar, recarrega a própria página em vez de tentar voltar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Removemos o DeleteAction para evitar deletar a configuração única acidentalmente
        ];
    }

    /**
     * Gera o webhook_token UUID automaticamente se não existir.
     * Garante que cada tenant tenha um URL único para receber webhooks PIX.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Gera webhook_token apenas se: provedor selecionado E token ainda não existe
        if (!empty($data['gateway_provider']) && empty($this->getRecord()->gateway_webhook_token)) {
            $data['gateway_webhook_token'] = (string) Str::uuid();
        }

        return $data;
    }
    /**
     * Sincroniza o webhook token com o banco central (Landlord)
     * para que o PixWebhookController consiga localizar o tenant pelo token.
     */
    protected function afterSave(): void
    {
        $tenant = filament()->getTenant();
        if ($tenant && current($this->getRecord()->only('gateway_webhook_token'))) {
            $tenant->update(['webhook_token' => $this->getRecord()->gateway_webhook_token]);
        }
    }

}

