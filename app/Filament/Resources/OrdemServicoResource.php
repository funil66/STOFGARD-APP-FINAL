<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdemServicoResource\Pages;
use App\Models\OrdemServico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Ordens de Serviço';

    protected static ?string $modelLabel = 'Ordem de Serviço';

    protected static ?string $pluralModelLabel = 'Ordens de Serviço';

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da OS')
                    ->schema([
                        Forms\Components\TextInput::make('numero_os')
                            ->label('Número da OS')
                            ->default(fn () => OrdemServico::gerarNumeroOS())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cadastro (Cliente, Loja ou Vendedor)')
                            ->options(function () {
                                $clientes = \App\Models\Cliente::all()->mapWithKeys(fn($c) => [
                                    'cliente_' . $c->id => '🧑 Cliente: ' . $c->nome
                                ]);
                                $parceiros = \App\Models\Parceiro::all()->mapWithKeys(fn($p) => [
                                    'parceiro_' . $p->id => ($p->tipo === 'loja' ? '🏪 Loja: ' : '🧑‍💼 Vendedor: ') . $p->nome
                                ]);
                                return $clientes->union($parceiros)->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Selecione um cliente, loja ou vendedor para esta OS.')
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('data_abertura')
                            ->label('Data de Abertura')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Serviço')
                    ->schema([
                        Forms\Components\Select::make('tipo_servico')
                            ->label('Tipo de Serviço')
                            ->options([
                                'Higienização de Estofados' => 'Higienização de Estofados',
                                'Impermeabilização' => 'Impermeabilização',
                                'Higienização + Impermeabilização' => 'Higienização + Impermeabilização',
                                'Limpeza de Carpetes' => 'Limpeza de Carpetes',
                                'Limpeza de Colchões' => 'Limpeza de Colchões',
                                'Hidratação de Couro' => 'Hidratação de Couro',
                                'Outros' => 'Outros',
                            ])
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Definir dias de garantia automaticamente
                                if (str_contains(strtolower($state), 'impermeabilização')) {
                                    $set('dias_garantia', 365); // 1 ano
                                } else {
                                    $set('dias_garantia', 90); // 90 dias
                                }

                                // Recalcular data fim garantia se já tem data de conclusão
                                $dataConclusao = $get('data_conclusao');
                                if ($dataConclusao) {
                                    $diasGarantia = $get('dias_garantia');
                                    $set('data_fim_garantia', \Carbon\Carbon::parse($dataConclusao)->addDays($diasGarantia));
                                }
                            })
                            ->columnSpan(2),

                        Forms\Components\Select::make('status')
                            ->options([
                                'aberta' => 'Aberta',
                                'em_andamento' => 'Em Andamento',
                                'aguardando_pecas' => 'Aguardando Peças',
                                'concluida' => 'Concluída',
                                'cancelada' => 'Cancelada',
                            ])
                            ->default('aberta')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('data_prevista')
                            ->label('Previsão de Conclusão')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('descricao_servico')
                            ->label('Descrição do Serviço')
                            ->required()
                            ->rows(4)
                            ->placeholder('Descreva detalhadamente o serviço a ser realizado...')
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Forms\Components\Section::make('Parceiro')
                    ->schema([
                        Forms\Components\Select::make('parceiro_id')
                            ->label('Loja/Vendedor Parceiro')
                            ->relationship('parceiro', 'nome')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $parceiro = \App\Models\Parceiro::find($state);
                                    if ($parceiro) {
                                        $set('percentual_comissao_os', $parceiro->percentual_comissao);

                                        // Recalcular comissão
                                        $valorTotal = floatval($get('valor_total') ?? 0);
                                        $percentual = floatval($parceiro->percentual_comissao ?? 30);
                                        $comissao = ($valorTotal * $percentual) / 100;
                                        $set('comissao_parceiro', $comissao);
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\Select::make('tipo')
                                    ->options([
                                        'loja' => 'Loja',
                                        'vendedor' => 'Vendedor',
                                    ])
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('nome')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('celular')
                                    ->tel()
                                    ->mask('(99) 99999-9999'),
                                Forms\Components\TextInput::make('percentual_comissao')
                                    ->label('% Comissão')
                                    ->numeric()
                                    ->default(30)
                                    ->suffix('%'),
                                Forms\Components\Hidden::make('registrado_por')
                                    ->default(fn () => strtoupper(substr(auth()->user()->name ?? 'ST', 0, 2))),
                            ])
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('numero_pedido_parceiro')
                            ->label('Nº Pedido do Parceiro')
                            ->maxLength(255)
                            ->placeholder('Ex: PED2026001')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('percentual_comissao_os')
                            ->label('% Comissão')
                            ->numeric()
                            ->default(30)
                            ->suffix('%')
                            ->suffixIcon('heroicon-m-pencil')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $valorTotal = floatval($get('valor_total') ?? 0);
                                $percentual = floatval($state ?? 30);
                                $comissao = ($valorTotal * $percentual) / 100;
                                $set('comissao_parceiro', $comissao);
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Valores')
                    ->schema([
                        Forms\Components\TextInput::make('valor_servico')
                            ->label('Valor do Serviço')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $valorServico = floatval($state ?? 0);
                                $valorProdutos = floatval($get('valor_produtos') ?? 0);
                                $valorDesconto = floatval($get('valor_desconto') ?? 0);
                                $valorTotal = ($valorServico + $valorProdutos) - $valorDesconto;
                                $set('valor_total', $valorTotal);

                                // Recalcular comissão
                                $percentual = floatval($get('percentual_comissao_os') ?? 30);
                                $comissao = ($valorTotal * $percentual) / 100;
                                $set('comissao_parceiro', $comissao);
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('valor_produtos')
                            ->label('Valor dos Produtos')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $valorServico = floatval($get('valor_servico') ?? 0);
                                $valorProdutos = floatval($state ?? 0);
                                $valorDesconto = floatval($get('valor_desconto') ?? 0);
                                $valorTotal = ($valorServico + $valorProdutos) - $valorDesconto;
                                $set('valor_total', $valorTotal);

                                // Recalcular comissão
                                $percentual = floatval($get('percentual_comissao_os') ?? 30);
                                $comissao = ($valorTotal * $percentual) / 100;
                                $set('comissao_parceiro', $comissao);
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('valor_desconto')
                            ->label('Desconto')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $valorServico = floatval($get('valor_servico') ?? 0);
                                $valorProdutos = floatval($get('valor_produtos') ?? 0);
                                $valorDesconto = floatval($state ?? 0);
                                $valorTotal = ($valorServico + $valorProdutos) - $valorDesconto;
                                $set('valor_total', $valorTotal);

                                // Recalcular comissão
                                $percentual = floatval($get('percentual_comissao_os') ?? 30);
                                $comissao = ($valorTotal * $percentual) / 100;
                                $set('comissao_parceiro', $comissao);
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('valor_total')
                            ->label('Valor Total')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('comissao_parceiro')
                            ->label('Comissão (Calculada)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Calculada automaticamente com base no % de comissão')
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('comissao_info')
                            ->label('')
                            ->content('')
                            ->columnSpan(1)
                            ->hidden(),

                        Forms\Components\Select::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->options([
                                'dinheiro' => '💵 Dinheiro',
                                'pix' => '🔲 PIX',
                                'cartao_credito' => '💳 Cartão de Crédito',
                                'cartao_debito' => '💳 Cartão de Débito',
                                'boleto' => '📄 Boleto',
                                'transferencia' => '🏦 Transferência',
                            ])
                            ->native(false)
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('pagamento_realizado')
                            ->label('Pagamento Realizado')
                            ->default(false)
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Forms\Components\Section::make('Garantia')
                    ->schema([
                        Forms\Components\TextInput::make('dias_garantia')
                            ->label('Dias de Garantia')
                            ->numeric()
                            ->default(90)
                            ->suffix('dias')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Definido automaticamente pelo tipo de serviço: 365 dias para impermeabilização, 90 dias para higienização')
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('data_conclusao')
                            ->label('Data de Conclusão')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $diasGarantia = $get('dias_garantia') ?? 90;
                                    $set('data_fim_garantia', \Carbon\Carbon::parse($state)->addDays($diasGarantia));
                                } else {
                                    $set('data_fim_garantia', null);
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('data_fim_garantia')
                            ->label('Fim da Garantia')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Fotos')
                    ->schema([
                        Forms\Components\FileUpload::make('fotos_antes')
                            ->label('Fotos Antes do Serviço')
                            ->image()
                            ->multiple()
                            ->maxFiles(10)
                            ->disk('public')
                            ->directory('ordens-servico/fotos-antes')
                            ->visibility('public')
                            ->imagePreviewHeight('180')
                            ->panelLayout('grid')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpan(2),

                        Forms\Components\FileUpload::make('fotos_depois')
                            ->label('Fotos Depois do Serviço')
                            ->image()
                            ->multiple()
                            ->maxFiles(10)
                            ->disk('public')
                            ->directory('ordens-servico/fotos-depois')
                            ->visibility('public')
                            ->imagePreviewHeight('180')
                            ->panelLayout('grid')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Observações')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações para o Cliente')
                            ->rows(3)
                            ->placeholder('Informações visíveis ao cliente...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observacoes_internas')
                            ->label('Observações Internas')
                            ->rows(3)
                            ->placeholder('Anotações internas da equipe...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_os')
                    ->label('Nº OS')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Número copiado'),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cadastro')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\ImageColumn::make('assinatura_cliente_path')
                    ->label('Assinatura')
                    ->disk('public')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->openUrlInNewTab()
                    ->square()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Serviço')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 25) {
                            return $state;
                        }

                        return null;
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'info' => 'aberta',
                        'warning' => 'em_andamento',
                        'danger' => 'aguardando_pecas',
                        'success' => 'concluida',
                        'gray' => 'cancelada',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aberta' => 'Aberta',
                        'em_andamento' => 'Em Andamento',
                        'aguardando_pecas' => 'Aguardando Peças',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_abertura')
                    ->label('Abertura')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('pagamento_realizado')
                    ->label('Pago')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('parceiro.nome')
                    ->label('Parceiro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'aberta' => 'Aberta',
                        'em_andamento' => 'Em Andamento',
                        'aguardando_pecas' => 'Aguardando Peças',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('cadastro_id')
                    ->label('Cadastro')
                    ->options(function () {
                        $clientes = \App\Models\Cliente::all()->mapWithKeys(fn($c) => [
                            'cliente_' . $c->id => '🧑 Cliente: ' . $c->nome
                        ]);
                        $parceiros = \App\Models\Parceiro::all()->mapWithKeys(fn($p) => [
                            'parceiro_' . $p->id => ($p->tipo === 'loja' ? '🏪 Loja: ' : '🧑‍💼 Vendedor: ') . $p->nome
                        ]);
                        return $clientes->union($parceiros)->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('data_abertura')
                    ->form([
                        Forms\Components\DatePicker::make('abertura_de')
                            ->label('Abertura de'),
                        Forms\Components\DatePicker::make('abertura_ate')
                            ->label('Abertura até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['abertura_de'], fn ($q, $date) => $q->whereDate('data_abertura', '>=', $date))
                            ->when($data['abertura_ate'], fn ($q, $date) => $q->whereDate('data_abertura', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('pagamento_realizado')
                    ->label('Pagamento')
                    ->placeholder('Todos')
                    ->trueLabel('Pagos')
                    ->falseLabel('Pendentes'),
            ])
            ->actions([
                Tables\Actions\Action::make('concluir_e_assinar')
                    ->label('Concluir e Assinar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->modalHeading('Assinatura do Cliente')
                    ->modalDescription('O cliente deve assinar abaixo para confirmar a execução do serviço.')
                    ->form([
                        Forms\Components\Hidden::make('assinatura_base64')
                            ->label('')
                            ->required(),

                        Forms\Components\ViewField::make('assinatura_base64')
                            ->view('filament.forms.components.signature-pad')
                            ->columnSpanFull(),
                    ])
                    ->action(function (OrdemServico $record, array $data) {
                        if (empty($data['assinatura_base64'])) {
                            return;
                        }

                        $image_parts = explode(';base64,', $data['assinatura_base64']);
                        if (count($image_parts) < 2) {
                            return;
                        }

                        $image_type_aux = explode('image/', $image_parts[0]);
                        $image_type = $image_type_aux[1] ?? 'png';
                        $image_base64 = base64_decode($image_parts[1]);

                        $filename = 'ordens-servico/assinaturas/os-' . $record->id . '-' . \Illuminate\Support\Str::random(10) . '.' . $image_type;
                        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $image_base64);

                        $record->update([
                            'status' => 'concluida',
                            'data_conclusao' => now(),
                            'assinatura_cliente_path' => $filename,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Serviço concluído e assinado com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (OrdemServico $record) => $record->status !== 'concluida'),
                Tables\Actions\Action::make('concluir_servico')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Concluir Serviço')
                    ->modalDescription('Ao concluir, será criado automaticamente um registro de garantia para este serviço.')
                    ->modalSubmitActionLabel('Sim, Concluir')
                    ->visible(fn ($record): bool => in_array($record->status, ['pendente', 'em_andamento']))
                    ->form([
                        Forms\Components\DatePicker::make('data_conclusao')
                            ->label('Data de Conclusão')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\FileUpload::make('fotos_resultado')
                            ->label('Fotos do Resultado')
                            ->image()
                            ->multiple()
                            ->maxFiles(10)
                            ->directory('ordens-servico/fotos-depois')
                            ->imagePreviewHeight(180)
                            ->panelLayout('grid')
                            ->helperText('Fotos do serviço concluído'),

                        Forms\Components\Textarea::make('observacoes_conclusao')
                            ->label('Observações da Conclusão')
                            ->rows(3)
                            ->placeholder('Detalhes sobre a execução do serviço, produtos utilizados, etc...'),
                    ])
                    ->action(function ($record, array $data): void {
                        \DB::transaction(function () use ($record, $data) {
                            // 1. Atualizar OS
                            $record->update([
                                'status' => 'concluida',
                                'data_conclusao' => $data['data_conclusao'],
                                'fotos_depois' => $data['fotos_resultado'] ?? null,
                                'observacoes' => ($record->observacoes ?? '')."\n\n".($data['observacoes_conclusao'] ?? ''),
                            ]);

                            // 2. Criar Garantia automaticamente
                            \App\Models\Garantia::create([
                                'ordem_servico_id' => $record->id,
                                'tipo_servico' => $record->tipo_servico,
                                'data_inicio' => $data['data_conclusao'],
                                'status' => 'ativa',
                                'observacoes' => 'Garantia criada automaticamente na conclusão da OS '.$record->numero_os,
                            ]);

                            // 3. Atualizar evento na agenda
                            if ($record->agendas()->exists()) {
                                $record->agendas()->update([
                                    'status' => 'concluido',
                                ]);
                            }
                        });
                    })
                    ->successNotificationTitle('Serviço concluído!')
                    ->successNotification(function () {
                        return \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Serviço Concluído!')
                            ->body('A garantia foi criada automaticamente.')
                            ->send();
                    }),

                Tables\Actions\Action::make('alterar_status')
                    ->label('Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Novo Status')
                            ->options([
                                'aberta' => 'Aberta',
                                'em_andamento' => 'Em Andamento',
                                'aguardando_pecas' => 'Aguardando Peças',
                                'concluida' => 'Concluída',
                                'cancelada' => 'Cancelada',
                            ])
                            ->default(fn ($record) => $record->status)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data) {
                        $updates = [
                            'status' => $data['status'],
                            'atualizado_por' => strtoupper(substr(auth()->user()->name, 0, 2)),
                        ];

                        // Se mudou para concluída, preencher data de conclusão e calcular garantia
                        if ($data['status'] === 'concluida' && empty($record->data_conclusao)) {
                            $updates['data_conclusao'] = now();
                            if ($record->dias_garantia) {
                                $updates['data_fim_garantia'] = now()->addDays($record->dias_garantia);
                            }
                        }

                        // Se mudou de concluída para outro status, limpar datas de conclusão e garantia
                        if ($data['status'] !== 'concluida' && $record->status === 'concluida') {
                            $updates['data_conclusao'] = null;
                            $updates['data_fim_garantia'] = null;
                        }

                        $record->update($updates);
                    })
                    ->successNotificationTitle('Status alterado com sucesso!'),

                Tables\Actions\Action::make('alterar_pagamento')
                    ->label('Pagamento')
                    ->icon('heroicon-o-banknotes')
                    ->color(fn ($record) => $record->pagamento_realizado ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->pagamento_realizado ? 'Marcar como NÃO pago?' : 'Marcar como pago?')
                    ->modalDescription(fn ($record) => $record->pagamento_realizado
                        ? 'O pagamento será marcado como pendente.'
                        : 'O pagamento será marcado como realizado.')
                    ->action(function ($record) {
                        $novo = ! $record->pagamento_realizado;

                        $record->update([
                            'pagamento_realizado' => $novo,
                            'atualizado_por' => strtoupper(substr(auth()->user()->name, 0, 2)),
                        ]);

                        // Sincronizar com o Financeiro, se existir lançamento vinculado
                        $transacao = \App\Models\TransacaoFinanceira::where('ordem_servico_id', $record->id)->first();
                        if ($transacao) {
                            if ($novo) {
                                $transacao->marcarComoPago(null, $record->forma_pagamento);
                            } else {
                                $transacao->update(['status' => 'pendente', 'data_pagamento' => null]);
                            }
                        }
                    })
                    ->successNotificationTitle('Status de pagamento alterado!'),

                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                \App\Filament\Actions\DownloadFileAction::make('assinatura_cliente_path', 'public')->label('Download'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações da OS')
                    ->schema([
                        Infolists\Components\TextEntry::make('numero_os')
                            ->label('Número da OS')
                            ->weight('bold')
                            ->size('lg')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('cliente.nome')
                            ->label('Cliente')
                            ->url(fn ($record) => $record->cliente ? url('/admin/cadastros') : null)
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'aberta' => 'info',
                                'em_andamento' => 'warning',
                                'aguardando_pecas' => 'danger',
                                'concluida' => 'success',
                                'cancelada' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'aberta' => 'Aberta',
                                'em_andamento' => 'Em Andamento',
                                'aguardando_pecas' => 'Aguardando Peças',
                                'concluida' => 'Concluída',
                                'cancelada' => 'Cancelada',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('data_abertura')
                            ->label('Data de Abertura')
                            ->date('d/m/Y'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Serviço')
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo_servico')
                            ->label('Tipo de Serviço'),

                        Infolists\Components\TextEntry::make('descricao_servico')
                            ->label('Descrição')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('data_prevista')
                            ->label('Previsão de Conclusão')
                            ->date('d/m/Y')
                            ->placeholder('Não definida'),

                        Infolists\Components\TextEntry::make('data_conclusao')
                            ->label('Data de Conclusão')
                            ->date('d/m/Y')
                            ->placeholder('Em andamento'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Valores')
                    ->schema([
                        Infolists\Components\TextEntry::make('valor_servico')
                            ->label('Serviço')
                            ->money('BRL'),

                        Infolists\Components\TextEntry::make('valor_produtos')
                            ->label('Produtos')
                            ->money('BRL'),

                        Infolists\Components\TextEntry::make('valor_desconto')
                            ->label('Desconto')
                            ->money('BRL'),

                        Infolists\Components\TextEntry::make('valor_total')
                            ->label('Total')
                            ->money('BRL')
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'dinheiro' => '💵 Dinheiro',
                                'pix' => '🔲 PIX',
                                'cartao_credito' => '💳 Cartão de Crédito',
                                'cartao_debito' => '💳 Cartão de Débito',
                                'boleto' => '📄 Boleto',
                                'transferencia' => '🏦 Transferência',
                                default => 'Não informado',
                            }),

                        Infolists\Components\IconEntry::make('pagamento_realizado')
                            ->label('Pagamento')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Parceiro')
                    ->schema([
                        Infolists\Components\TextEntry::make('parceiro.nome')
                            ->label('Nome')
                            ->placeholder('Sem parceiro'),

                        Infolists\Components\TextEntry::make('numero_pedido_parceiro')
                            ->label('Nº Pedido')
                            ->placeholder('Não informado'),

                        Infolists\Components\TextEntry::make('comissao_parceiro')
                            ->label('Comissão')
                            ->money('BRL'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Garantia')
                    ->schema([
                        Infolists\Components\ViewEntry::make('status_garantia')
                            ->label('Status da Garantia')
                            ->view('filament.infolists.garantia-status')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('dias_garantia')
                            ->label('Período Padrão')
                            ->suffix(' dias')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('data_conclusao')
                            ->label('Início (Conclusão)')
                            ->date('d/m/Y')
                            ->placeholder('Aguardando conclusão'),

                        Infolists\Components\TextEntry::make('data_fim_garantia')
                            ->label('Válida até')
                            ->date('d/m/Y')
                            ->placeholder('Aguardando conclusão'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Fotos do Serviço')
                    ->schema([
                        Infolists\Components\ImageEntry::make('fotos_antes')
                            ->label('Antes')
                            ->disk('public')
                            ->visibility('public')
                            ->limit(10)
                            ->height(250)
                            ->openUrlInNewTab()
                            ->columnSpan(1),

                        Infolists\Components\ImageEntry::make('fotos_depois')
                            ->label('Depois')
                            ->disk('public')
                            ->visibility('public')
                            ->limit(10)
                            ->height(250)
                            ->openUrlInNewTab()
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('fotos_actions')
                            ->label('Ações de arquivos')
                            ->html()
                            ->getStateUsing(function ($record) {
                                $entries = [];

                                foreach (['fotos_antes', 'fotos_depois'] as $attr) {
                                    foreach (data_get($record, $attr) ?? [] as $path) {
                                        $name = basename($path);

                                        $downloadUrl = route('admin.files.download', [
                                            'model' => base64_encode(get_class($record)),
                                            'record' => $record->getKey(),
                                            'path' => base64_encode($path),
                                        ]);

                                        $deleteUrl = \Illuminate\Support\Facades\URL::signedRoute('admin.files.delete', [
                                            'model' => base64_encode(get_class($record)),
                                            'record' => $record->getKey(),
                                            'path' => base64_encode($path),
                                        ], now()->addHour());

                                        $entries[] = "<div class='py-1'><strong>{$attr}:</strong> <a href='{$downloadUrl}' target='_blank' class='text-sm text-blue-600 underline'>{$name}</a> · <a href='{$downloadUrl}?download=1' class='text-sm'>Baixar</a> · <a href='{$deleteUrl}' class='text-sm text-red-600 ml-2' onclick=\"return confirm('Excluir arquivo?')\">Excluir</a></div>";
                                    }
                                }

                                return implode('', $entries);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                    Infolists\Components\Section::make('Assinatura')
                        ->schema([
                            Infolists\Components\ImageEntry::make('assinatura_cliente_path')
                                ->label('Assinatura do Cliente')
                                ->disk('public')
                                ->visibility('public')
                                ->height(220)
                                ->openUrlInNewTab()
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->collapsible()
                        ->collapsed(),

                Infolists\Components\Section::make('Observações')
                    ->schema([
                        Infolists\Components\TextEntry::make('observacoes')
                            ->label('Para o Cliente')
                            ->placeholder('Nenhuma observação')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('observacoes_internas')
                            ->label('Internas')
                            ->placeholder('Nenhuma observação interna')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Informações do Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('criado_por')
                            ->label('Criado por'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrdemServicos::route('/'),
            'create' => Pages\CreateOrdemServico::route('/create'),
            'view' => Pages\ViewOrdemServico::route('/{record}'),
            'edit' => Pages\EditOrdemServico::route('/{record}/edit'),
        ];
    }
}
