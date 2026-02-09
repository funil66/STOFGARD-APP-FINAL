<?php

namespace App\Filament\Resources\ConfiguracaoResource\Pages;

use App\Filament\Resources\ConfiguracaoResource;
use Filament\Resources\Pages\EditRecord;

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
}
