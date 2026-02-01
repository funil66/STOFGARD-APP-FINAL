<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use App\Models\Agenda;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request as HttpRequest;
use App\Http\Controllers\OrcamentoPdfController;
use App\Services\PixService;

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
                ->visible(fn($record): bool => $record->status === 'pendente')
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
                            // Prefer explicit endereco_completo when present (some imports/seeders store it)
                            if (!empty($cadastro?->endereco_completo)) {
                                return $cadastro->endereco_completo;
                            }
                            return trim(implode(', ', array_filter([
                                $cadastro?->logradouro,
                                $cadastro && ($cadastro->numero ?? false) ? "nº {$cadastro->numero}" : null,
                                $cadastro?->complemento,
                                $cadastro?->bairro,
                                $cadastro?->cidade,
                                $cadastro?->estado,
                                $cadastro?->cep ? "CEP: {$cadastro->cep}" : null,
                            ])));
                        })
                        ->helperText('Endereço completo onde o serviço será realizado (pode ser editado)'),

                    Forms\Components\Textarea::make('observacoes_os')
                        ->label('📝 Observações para a OS')
                        ->rows(3)
                        ->placeholder('Observações adicionais para a Ordem de Serviço...'),
                ])
                ->action(function ($record, array $data): void {
                    DB::transaction(function () use ($record, $data) {
                        // 1. Criar Ordem de Serviço
                        $cadastro = $record->cliente; // Uses cliente() relationship -> Cadastro
                        // Use the edited location from form instead of auto-generating
                        $enderecoCompleto = $data['local_servico'] ?? 'Endereço não informado';

                        $osData = [
                            'numero_os' => OrdemServico::gerarNumeroOS(),
                            'orcamento_id' => $record->id,
                            'cadastro_id' => $record->cadastro_id, // Unified Cadastro
                            'loja_id' => $record->loja_id,
                            'vendedor_id' => $record->vendedor_id,
                            'tipo_servico' => $record->tipo_servico ?? 'servico',
                            'descricao_servico' => $record->descricao_servico ?? 'Conforme orçamento ' . $record->numero,
                            'data_abertura' => now(),
                            'data_prevista' => $data['data_servico'] ?? null,
                            'status' => 'pendente',
                            'valor_total' => $record->valor_total,
                            'observacoes' => $data['observacoes_os'] ?? $record->observacoes,
                            'criado_por' => auth()->user()->name ?? auth()->id(),
                        ];

                        $os = OrdemServico::create($osData);

                        // Copiar itens do orçamento para a OS
                        foreach ($record->itens as $item) {
                            OrdemServicoItem::create([
                                'ordem_servico_id' => $os->id,
                                'descricao' => $item->item_nome ?? $item->descricao_item ?? 'Serviço',
                                'quantidade' => $item->quantidade,
                                'unidade_medida' => $item->unidade ?? $item->unidade_medida ?? 'un',
                                'valor_unitario' => $item->valor_unitario,
                                'subtotal' => $item->subtotal,
                            ]);
                        }

                        // 2. Criar registro na Agenda (APENAS SE TIVER DATA)
                        if (!empty($data['data_servico'])) {
                            $dataServico = \Carbon\Carbon::parse($data['data_servico']);
                            $horaInicio = \Carbon\Carbon::parse($data['hora_inicio'] ?? '09:00');
                            $horaFim = \Carbon\Carbon::parse($data['hora_fim'] ?? '17:00');

                            $agenda = Agenda::create([
                                'titulo' => sprintf(
                                    '%s - %s',
                                    match ($record->tipo_servico ?? 'servico') {
                                        'higienizacao' => '🧼 Higienização',
                                        'impermeabilizacao' => '💧 Impermeabilização',
                                        'higienizacao_impermeabilizacao' => '🧼💧 Hig + Imper',
                                        default => 'Serviço',
                                    },
                                    $cadastro?->nome ?? 'Cliente'
                                ),
                                'descricao' => $record->descricao_servico ?? ('Conforme orçamento ' . $record->numero),
                                'cadastro_id' => $record->cadastro_id,
                                'ordem_servico_id' => $os->id,
                                'orcamento_id' => $record->id,
                                'tipo' => 'servico',
                                'data_hora_inicio' => $dataServico->copy()->setTimeFromTimeString($horaInicio->format('H:i:s')),
                                'data_hora_fim' => $dataServico->copy()->setTimeFromTimeString($horaFim->format('H:i:s')),
                                'status' => 'agendado',
                                'local' => $enderecoCompleto ?: 'Endereço não informado',
                                'endereco_completo' => $enderecoCompleto,
                                'observacoes' => $data['observacoes_os'] ?? ('Agendado automaticamente - ' . $record->numero),
                                'cor' => match ($record->tipo_servico ?? 'servico') {
                                    'higienizacao' => '#3b82f6',
                                    'impermeabilizacao' => '#f59e0b',
                                    'higienizacao_impermeabilizacao' => '#10b981',
                                    default => '#6b7280',
                                },
                                'criado_por' => auth()->id(),
                            ]);
                        }

                        // 3. Criar lançamento no Financeiro (Conta a Receber) - USANDO MODEL CORRETO
                        \App\Models\Financeiro::create([
                            'tipo' => 'entrada',
                            'categoria' => 'servico',
                            'descricao' => sprintf(
                                'Serviço - OS %s - Cliente: %s',
                                $os->numero_os,
                                $cadastro?->nome ?? 'Cliente'
                            ),
                            'valor' => $record->valor_total,
                            // Se não tem data serviço, usa hoje como base
                            'data' => $data['data_servico'] ?? now(),
                            'data_vencimento' => $data['data_servico'] ?? now()->addDays(30),
                            'status' => 'pendente',
                            'forma_pagamento' => $record->forma_pagamento ?? null,
                            'cadastro_id' => $record->cadastro_id,
                            'ordem_servico_id' => $os->id,
                            'orcamento_id' => $record->id,
                        ]);

                        // 4. Atualizar orçamento com link para OS
                        $record->update([
                            'status' => 'aprovado',
                        ]);
                    });

                    Notification::make()
                        ->success()
                        ->title('Orçamento Aprovado!')
                        ->body('A Ordem de Serviço, Agenda e Financeiro foram criados automaticamente.')
                        ->send();
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
                    // Montar uma Request e delegar ao controller (reaproveita a lógica já criada)
                    $request = new HttpRequest($data);
                    $controller = app(OrcamentoPdfController::class);

                    $response = $controller->generateAndSave($request, $record, app(PixService::class));

                    // Interpreta resposta (JSON com url)
                    try {
                        $payload = json_decode($response->getContent(), true);
                        if (isset($payload['url'])) {
                            Notification::make()
                                ->success()
                                ->title('PDF Gerado')
                                ->body("PDF salvo: <a href='{$payload['url']}' target='_blank'>Abrir PDF</a>")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Erro')
                                ->body('Não foi possível gerar o PDF. Veja logs para detalhes.')
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Erro')
                            ->body('Erro ao processar resposta: ' . $e->getMessage())
                            ->send();
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
