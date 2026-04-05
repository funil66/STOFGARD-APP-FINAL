<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use App\Http\Controllers\OrcamentoPdfController;
use App\Models\Agenda;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewOrcamento extends ViewRecord
{
    protected static string $resource = OrcamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn($record): bool => $record->status !== 'convertido'),

            // #5: Aprovar e Gerar OS (Unificado)
            \App\Filament\Actions\OrcamentoActions::getAprovarAction(),

            Actions\Action::make('baixar_pdf_direto')
                ->label('Baixar PDF Agora')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn (Orcamento $record) => route('orcamento.pdf', ['orcamento' => $record->id]), shouldOpenInNewTab: true),

            Actions\Action::make('gerar_pdf_background')
                ->label('Gerar PDF (Fila)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('secondary')
                ->requiresConfirmation()
                ->modalHeading('Gerar Documento Pesado')
                ->modalDescription('O PDF será gerado em segundo plano para não travar sua tela. Você receberá uma notificação quando estiver pronto.')
                ->form([
                    Forms\Components\Toggle::make('include_pix')
                        ->label('Incluir QR Code PIX')
                        ->default(fn($record) => (bool) ($record->pdf_incluir_pix ?? true)),

                    Forms\Components\Toggle::make('persist')
                        ->label('Salvar preferência (persistir)')
                        ->helperText('Se marcado, salva a preferência em pdf_incluir_pix do orçamento')
                        ->default(false),
                ])
                ->action(function ($record, array $data) {
                    if ($data['persist'] ?? false) {
                        $record->update([
                            'pdf_incluir_pix' => $data['include_pix'] ?? true,
                        ]);
                    } else {
                        $record->pdf_incluir_pix = $data['include_pix'] ?? true;
                    }

                    $settingsArray = \App\Models\Setting::pluck('value', 'key')->toArray();
                    $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
                    foreach ($jsonFields as $k) {
                        if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                            $settingsArray[$k] = json_decode($settingsArray[$k], true);
                        }
                    }
                    $config = (object) $settingsArray;

                    // Lógica do PIX movida temporariamente para gerar HTML completo
                    if ($record->pdf_incluir_pix && $record->pix_chave_selecionada) {
                        try {
                            $percentualPix = $record->pdf_desconto_pix_percentual !== null ? floatval($record->pdf_desconto_pix_percentual) : floatval($settingsArray['financeiro_desconto_avista'] ?? 10);
                            $descontos = $record->getValorComDescontos($percentualPix);
                            $valorFinal = $descontos['valor_final'];
                            $chavesPix = $config->financeiro_pix_keys ?? [];
                            $titular = $settingsArray['nome_sistema'] ?? 'Autonomia Ilimitada';
                            if (is_array($chavesPix)) {
                                foreach ($chavesPix as $keyItem) {
                                    if ($keyItem['chave'] === $record->pix_chave_selecionada) {
                                        $titular = $keyItem['titular'] ?? $titular;
                                        break;
                                    }
                                }
                            }
                            $pixService = new \App\Services\Pix\PixMasterService();
                            $pixData = $pixService->gerarQrCode($record->pix_chave_selecionada, $titular, 'Ribeirao Preto', $record->numero ?? 'ORC', $valorFinal);
                            $record->pix_qrcode_base64 = $pixData['qr_code_img'];
                            $record->pix_copia_cola = $pixData['payload_pix'];
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Erro gerar PIX PDF Background: " . $e->getMessage());
                        }
                    }

                    $htmlContent = view('pdf.orcamento', ['orcamento' => $record, 'config' => $config])->render();

                    \App\Services\PdfQueueService::enqueue($record->id, 'orcamento', auth()->id(), $htmlContent);

                    Notification::make()
                        ->title('🚀 Fogo na Bomba!')
                        ->body('O PDF do Orçamento está sendo gerado no servidor. Continue trabalhando, avisaremos quando estiver pronto.')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),

            \Filament\Actions\Action::make('whatsapp_background')
                ->label('Enviar WhatsApp (Fila)')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Disparar WhatsApp Automático')
                ->modalDescription('O link mágico deste orçamento será enviado automaticamente para o WhatsApp do cliente através da API Evolution.')
                ->action(function (Orcamento $record) {
                    $pdfUrl = \Illuminate\Support\Facades\URL::signedRoute('orcamento.public_stream', ['orcamento' => $record->id], now()->addDays(7));
                    $phone = preg_replace('/[^0-9]/', '', $record->cliente->telefone ?? '');

                    if (empty($phone)) {
                        Notification::make()
                            ->danger()
                            ->title('Telefone Inválido')
                            ->body('O cliente não possui um telefone cadastrado para envio.')
                            ->send();
                        return;
                    }

                    $text = "Olá {$record->cliente->nome}, aqui está o seu orçamento #{$record->id} da Autonomia Ilimitada.\n\nClique para visualizar: {$pdfUrl}";

                    \App\Jobs\SendWhatsAppJob::dispatch($phone, $text, 'default');

                    Notification::make()
                        ->title('📱 Disparo Autorizado!')
                        ->body("A mensagem foi enviada para a fila de disparo do WhatsApp para o número {$phone}.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
