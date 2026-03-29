<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrcamento extends EditRecord
{
    protected static string $resource = OrcamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('gerar_pdf_background')
                ->label('Gerar PDF (Fila)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Gerar Documento Pesado')
                ->modalDescription('O PDF será gerado em segundo plano para não travar sua tela. Você receberá uma notificação quando estiver pronto.')
                ->action(function ($record) {
                    $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
                    $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
                    foreach ($jsonFields as $k) {
                        if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                            $decoded = json_decode($settingsArray[$k], true);
                            $settingsArray[$k] = $decoded !== null ? $decoded : [];
                        } elseif (!isset($settingsArray[$k])) {
                            $settingsArray[$k] = [];
                        }
                    }
                    $config = (object) $settingsArray;

                    try {
                        $htmlContent = view('pdf.orcamento', ['orcamento' => $record, 'config' => $config])->render();

                        \App\Jobs\ProcessPdfJob::dispatch(
                            $record->id,
                            'orcamento',
                            auth()->id(),
                            $htmlContent
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('🚀 Fogo na Bomba!')
                            ->body('O PDF está sendo gerado no servidor. Continue trabalhando, avisaremos quando estiver pronto.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro Crítico')
                            ->body('Falha ao instanciar o gerador de PDF. Erro: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        app(\App\Actions\Financeiro\CalculateOrcamentoTotalsAction::class)->execute($this->record);
    }
}
