<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use App\Models\Agenda;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use Filament\Actions;
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

            Actions\Action::make('gerar_pdf_background')
                ->label('Gerar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('secondary')
                ->requiresConfirmation()
                ->modalHeading('Gerar PDF')
                ->modalDescription('O PDF será gerado em segundo plano e ficará disponível em PDFs Gerados.')
                ->url(fn (Orcamento $record) => route('orcamento.pdf', ['orcamento' => $record->id])),

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
