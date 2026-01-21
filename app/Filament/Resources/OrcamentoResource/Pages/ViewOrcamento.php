<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use App\Models\Agenda;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use App\Models\TransacaoFinanceira;
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
                ->visible(fn ($record): bool => $record->status !== 'convertido'),

            Actions\Action::make('aprovar')
                ->label('Aprovar e Gerar OS')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprovar Orçamento e Gerar OS')
                ->modalDescription('Ao aprovar, será criada a Ordem de Serviço, o agendamento e o lançamento financeiro. Por favor, confirme a data do serviço.')
                ->modalSubmitActionLabel('Sim, Aprovar')
                ->visible(fn ($record): bool => $record->status === 'pendente')
                ->form([
                    Forms\Components\DatePicker::make('data_servico')
                        ->label('Data do Serviço')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->default(now()->addDays(3))
                        ->helperText('Data prevista para execução do serviço'),

                    Forms\Components\TimePicker::make('hora_inicio')
                        ->label('Hora de Início')
                        ->required()
                        ->default('09:00')
                        ->native(false),

                    Forms\Components\TimePicker::make('hora_fim')
                        ->label('Hora de Término (estimada)')
                        ->required()
                        ->default('17:00')
                        ->native(false),

                    Forms\Components\Textarea::make('observacoes_os')
                        ->label('Observações para a OS')
                        ->rows(3)
                        ->placeholder('Observações adicionais para a Ordem de Serviço...'),
                ])
                ->action(function ($record, array $data): void {
                    DB::transaction(function () use ($record, $data) {
                        // 1. Criar Ordem de Serviço
                        $cadastro = $record->cadastro;
                        $enderecoCompleto = trim(implode(', ', array_filter([
                            $cadastro?->logradouro,
                            $cadastro && ($cadastro->numero ?? false) ? "nº {$cadastro->numero}" : null,
                            $cadastro?->complemento,
                            $cadastro?->bairro,
                            $cadastro?->cidade,
                            $cadastro?->estado,
                            $cadastro?->cep ? "CEP: {$cadastro->cep}" : null,
                        ])));

                        $osData = [
                            'numero_os' => OrdemServico::gerarNumeroOS(),
                            'orcamento_id' => $record->id,
                            'tipo_servico' => $record->tipo_servico,
                            'descricao_servico' => $record->descricao_servico ?? 'Conforme orçamento '.$record->numero_orcamento,
                            'data_abertura' => now(),
                            'data_prevista' => $data['data_servico'],
                            'status' => 'pendente',
                            'valor_total' => $record->valor_total,
                            'forma_pagamento' => $record->forma_pagamento,
                            'observacoes' => $data['observacoes_os'] ?? $record->observacoes,
                            'criado_por' => auth()->user()->name,
                        ];

                        // Vincular cadastro unificado e compatibilidade legada
                        if ($record->cadastro_id) {
                            $osData['cadastro_id'] = $record->cadastro_id;
                            if (str_starts_with($record->cadastro_id, 'cliente_')) {
                                $osData['cliente_id'] = (int) str_replace('cliente_', '', $record->cadastro_id);
                            }
                            if (str_starts_with($record->cadastro_id, 'parceiro_')) {
                                $osData['parceiro_id'] = (int) str_replace('parceiro_', '', $record->cadastro_id);
                            }
                        } else {
                            $osData['cliente_id'] = $record->cliente_id;
                        }

                        $os = OrdemServico::create($osData);

                        // Copiar itens do orçamento para a OS
                        foreach ($record->itens as $item) {
                            OrdemServicoItem::create([
                                'ordem_servico_id' => $os->id,
                                'descricao' => $item->descricao_item,
                                'quantidade' => $item->quantidade,
                                'unidade_medida' => $item->unidade_medida,
                                'valor_unitario' => $item->valor_unitario,
                                'subtotal' => $item->subtotal,
                            ]);
                        }

                        // 2. Criar registro na Agenda
                        $dataServico = \Carbon\Carbon::parse($data['data_servico']);
                        $horaInicio = \Carbon\Carbon::parse($data['hora_inicio']);
                        $horaFim = \Carbon\Carbon::parse($data['hora_fim']);

                        $agenda = Agenda::create([
                            'titulo' => sprintf(
                                '%s - %s',
                                match ($record->tipo_servico) {
                                    'higienizacao' => '🧼 Higienização',
                                    'impermeabilizacao' => '💧 Impermeabilização',
                                    'higienizacao_impermeabilizacao' => '🧼💧 Hig + Imper',
                                    default => 'Serviço',
                                },
                                $cadastro?->nome
                            ),
                            'descricao' => $record->descricao_servico ?? ('Conforme orçamento '.$record->numero_orcamento),
                            'cliente_id' => $os->cliente_id ?? null,
                            'cadastro_id' => $record->cadastro_id ?? ($os->cadastro_id ?? ($os->cliente_id ? 'cliente_' . $os->cliente_id : null)),
                            'ordem_servico_id' => $os->id,
                            'orcamento_id' => $record->id,
                            'tipo' => 'servico',
                            'data_hora_inicio' => $dataServico->setTimeFromTimeString($horaInicio->format('H:i:s')),
                            'data_hora_fim' => $dataServico->copy()->setTimeFromTimeString($horaFim->format('H:i:s')),
                            'status' => 'agendado',
                            'local' => $enderecoCompleto ?: 'Endereço não informado',
                            'endereco_completo' => $enderecoCompleto,
                            'observacoes' => $data['observacoes_os'] ?? ('Agendado automaticamente - '.$record->numero_orcamento),
                            'cor' => match ($record->tipo_servico) {
                                'higienizacao' => '#3b82f6',
                                'impermeabilizacao' => '#f59e0b',
                                'higienizacao_impermeabilizacao' => '#10b981',
                                default => '#6b7280',
                            },
                            'criado_por' => auth()->user()->name,
                        ]);

                        // Atualizar OS com o ID da agenda
                        $os->update(['agenda_id' => $agenda->id]);

                        // 3. Criar lançamento no Financeiro (a receber)
                        TransacaoFinanceira::create([
                            'tipo' => 'receita',
                            'categoria' => 'servico',
                            'descricao' => sprintf(
                                'Serviço - OS %s - Cliente: %s',
                                $os->numero_os,
                                $cadastro?->nome
                            ),
                            'valor' => $record->valor_total,
                            'data_transacao' => $data['data_servico'],
                            'data_vencimento' => $data['data_servico'],
                            'status' => 'pendente',
                            'metodo_pagamento' => $record->forma_pagamento,
                            'cadastro_id' => $record->cadastro_id ?? null,
                            'cliente_id' => $os->cliente_id ?? null,
                            'parceiro_id' => $os->parceiro_id ?? null,
                        ]);

                        // 4. Atualizar orçamento com link para OS
                        $record->update([
                            'ordem_servico_id' => $os->id,
                            'status' => 'convertido',
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
                        ->default(fn ($record) => (bool) ($record->pdf_incluir_pix ?? true)),

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
                            ->body('Erro ao processar resposta: '.$e->getMessage())
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
