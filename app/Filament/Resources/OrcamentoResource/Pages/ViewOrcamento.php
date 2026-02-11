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

            Actions\Action::make('aprovar')
                ->label('Aprovar e Gerar OS')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->slideOver()
                ->modalWidth('3xl')
                ->modalHeading('Aprovar Orçamento e Gerar OS')
                ->modalDescription('Configure a data e horário do serviço. Após aprovação, será criada a Ordem de Serviço, o agendamento e o lançamento financeiro.')
                ->modalSubmitActionLabel('✓ Aprovar e Criar Registros')
                ->visible(fn($record): bool => in_array($record->status, ['rascunho', 'pendente', 'enviado']))
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('data_servico')
                                ->label('📅 Data do Serviço (Opcional)')
                                ->nullable()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Deixe em branco se ainda não houver data definida')
                                ->columnSpan(2),

                            Forms\Components\TimePicker::make('hora_inicio')
                                ->label('🕐 Hora de Início')
                                ->default('09:00')
                                ->native(false)
                                ->columnSpan(1)
                                ->visible(fn(\Filament\Forms\Get $get) => filled($get('data_servico'))),

                            Forms\Components\TimePicker::make('hora_fim')
                                ->label('🕐 Hora de Término (estimada)')
                                ->default('17:00')
                                ->native(false)
                                ->columnSpan(1)
                                ->visible(fn(\Filament\Forms\Get $get) => filled($get('data_servico'))),
                        ]),

                    Forms\Components\Textarea::make('local_servico')
                        ->label('📍 Local do Serviço')
                        ->required()
                        ->rows(2)
                        ->default(function ($record) {
                            $cadastro = $record->cliente;

                            return $cadastro?->formatEnderecoCompleto() ?? '';
                        })
                        ->helperText('Endereço completo onde o serviço será realizado (pode ser editado)'),

                    Forms\Components\Textarea::make('observacoes_os')
                        ->label('📝 Observações para a OS')
                        ->rows(3)
                        ->placeholder('Observações adicionais para a Ordem de Serviço...'),
                ])
                ->action(function ($record, array $data): void {
                    try {
                        $stofgard = app(\App\Services\StofgardSystem::class);

                        $stofgard->aprovarOrcamento($record, auth()->id(), [
                            'data_servico' => $data['data_servico'] ?? null,
                            'hora_inicio' => $data['hora_inicio'] ?? null,
                            'hora_fim' => $data['hora_fim'] ?? null,
                            'local_servico' => $data['local_servico'] ?? null,
                            'observacoes' => $data['observacoes_os'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Orçamento Aprovado!')
                            ->body('A Ordem de Serviço, Agenda e Financeiro foram criados automaticamente.')
                            ->success()
                            ->send();

                        // Recarregar a página para atualizar status
                        redirect(request()->header('Referer'));

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro ao aprovar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

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
