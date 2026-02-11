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

            Actions\Action::make('gerar_pdf')
                ->label('Gerar PDF')
                ->icon('heroicon-o-document-text')
                ->color('secondary')
                ->modalHeading('Gerar PDF do Orçamento')
                ->modalWidth('md')
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
                    // Atualizar preferência de PIX se solicitado
                    if ($data['persist'] ?? false) {
                        $record->update([
                            'pdf_incluir_pix' => $data['include_pix'] ?? true,
                        ]);
                    } else {
                        // Apenas atualiza temporariamente para esta geração
                        $record->pdf_incluir_pix = $data['include_pix'] ?? true;
                    }

                    // Gerar PDF usando o controller
                    try {
                        $controller = app(OrcamentoPdfController::class);

                        return $controller->gerarPdf($record);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Erro ao gerar PDF')
                            ->body($e->getMessage())
                            ->send();

                        return null;
                    }
                }),

            Actions\DeleteAction::make(),
            \Filament\Actions\Action::make('whatsapp')
                ->label('Enviar WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn(Orcamento $record) => $this->getWhatsappUrl($record))
                ->openUrlInNewTab(),
        ];
    }

    // Método auxiliar para gerar o Link Mágico
    protected function getWhatsappUrl(Orcamento $record): string
    {
        // 1. Gera o Link Público Assinado (válido por 7 dias, por exemplo)
        $pdfUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'orcamento.public_stream',
            ['orcamento' => $record->id],
            now()->addDays(7)
        );

        // 2. Formata o telefone (remove caracteres não numéricos)
        $phone = preg_replace('/[^0-9]/', '', $record->cliente->telefone ?? '');

        // 3. Monta a mensagem
        $text = urlencode("Olá {$record->cliente->nome}, aqui está o seu orçamento #{$record->id} da Stofgard.\n\nClique para visualizar: {$pdfUrl}");

        // 4. Retorna link do WhatsApp API
        // Se não tiver telefone, abre apenas a janela para escolher o contato
        return $phone
            ? "https://wa.me/55{$phone}?text={$text}"
            : "https://wa.me/?text={$text}";
    }
}
