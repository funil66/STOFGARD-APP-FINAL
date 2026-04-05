<?php

namespace App\Filament\Resources\GarantiaResource\Pages;

use App\Filament\Resources\GarantiaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGarantia extends CreateRecord
{
    protected static string $resource = GarantiaResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
        $config = (object) $settingsArray;

        try {
            $record->load(['ordemServico.cliente']);
            $htmlContent = view('pdf.certificado_garantia', [
                'garantia' => $record,
                'os' => $record->ordemServico,
                'config' => $config
            ])->render();
            
            \App\Services\PdfQueueService::enqueue(
                $record->id,
                'garantia',
                auth()->id(),
                $htmlContent
            );

            \Filament\Notifications\Notification::make()
                ->title('📄 Documento Gerado')
                ->body('O Certificado de Garantia está sendo gerado em segundo plano e logo estará disponível.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Erro ao compilar documento')
                ->body('Erro: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
