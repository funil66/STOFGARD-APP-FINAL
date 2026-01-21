<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrcamentoResource\Pages;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\TabelaPreco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Orçamentos';

    protected static ?string $modelLabel = 'Orçamento';

    protected static ?string $pluralModelLabel = 'Orçamentos';

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 4;

    public static function getItensHigienizacaoOptions(): array
    {
        return TabelaPreco::ativos()
            ->where('tipo_servico', 'higienizacao')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => "🧼 {$item->nome_item}"];
            })
            ->toArray();
    }

    public static function getItensImpermeabilizacaoOptions(): array
    {
        return TabelaPreco::ativos()
            ->where('tipo_servico', 'impermeabilizacao')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => "💧 {$item->nome_item}"];
            })
            ->toArray();
    }

    public static function getClientesOptions(): array
    {
        return Cliente::orderBy('nome')
            ->get()
            ->mapWithKeys(function ($c) {
                $label = $c->nome;
                if ($c->celular) {
                    $label .= " ({$c->celular})";
                }

                return [$c->id => $label];
            })
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Informações do Orçamento
                Forms\Components\Section::make('Informações do Orçamento')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_orcamento')
                                    ->label('Número do Orçamento')
                                    ->disabled()
                                    ->dehydrated()
                                    ->placeholder('Gerado automaticamente')
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('data_orcamento')
                                    ->label('Data do Orçamento')
                                    ->default(now())
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('data_validade')
                                    ->label('Válido até')
                                    ->default(now()->addDays(7))
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Orçamento válido por 7 dias')
                                    ->columnSpan(1),
                            ]),

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
                            ->helperText('Selecione um cliente, loja ou vendedor para este orçamento.'),

                        Forms\Components\TextInput::make('numero_pedido_parceiro')
                            ->label('Número do Pedido do Parceiro')
                            ->helperText('Número de referência do parceiro/vendedor'),

                        Forms\Components\Hidden::make('tipo_servico')
                            ->default('higienizacao_impermeabilizacao'),

                        Forms\Components\Textarea::make('descricao_servico')
                            ->label('Descrição do Serviço')
                            ->required()
                            ->rows(3)
                            ->default('Conforme especificado nos itens do orçamento')
                            ->helperText('Descrição detalhada do serviço a ser realizado')
                            ->columnSpanFull(),
                    ]),

                // Seção: Itens de Higienização
                Forms\Components\Section::make('Itens de Higienização 🧼')
                    ->schema([
                        Forms\Components\Repeater::make('itens_higienizacao')
                            ->relationship('itensHigienizacao')
                            ->schema([
                                Forms\Components\Select::make('tabela_preco_id')
                                    ->label('Item da Tabela')
                                    ->placeholder('Selecione o Serviço')
                                    ->options(self::getItensHigienizacaoOptions())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            $item = TabelaPreco::find($state);
                                            if ($item) {
                                                $set('descricao_item', $item->nome_item);
                                                $set('unidade_medida', $item->unidade_medida);
                                                $set('valor_unitario', $item->preco_vista);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('descricao_item')
                                    ->label('Descrição')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\Select::make('unidade_medida')
                                    ->label('Unidade')
                                    ->options([
                                        'unidade' => 'UN',
                                        'm2' => 'M²',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('quantidade')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('valor_unitario')
                                    ->label('Valor Unit.')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('subtotal_item')
                                    ->label('Subtotal')
                                    ->content(function (Get $get): string {
                                        $qtd = (float) ($get('quantidade') ?? 0);
                                        $valor = (float) ($get('valor_unitario') ?? 0);
                                        $subtotal = $qtd * $valor;

                                        return 'R$ '.number_format($subtotal, 2, ',', '.');
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(6)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->collapsed(false)
                            ->itemLabel(fn (array $state): ?string => $state['descricao_item'] ?? null)
                            ->addActionLabel('➕ Adicionar Item de Higienização')
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action
                                    ->requiresConfirmation()
                            ),
                    ])
                    ->description('Adicione itens de higienização')
                    ->collapsible(),

                // Seção: Itens de Impermeabilização
                Forms\Components\Section::make('Itens de Impermeabilização 💧')
                    ->schema([
                        Forms\Components\Repeater::make('itens_impermeabilizacao')
                            ->relationship('itensImpermeabilizacao')
                            ->schema([
                                Forms\Components\Select::make('tabela_preco_id')
                                    ->label('Item da Tabela')
                                    ->placeholder('Selecione o Serviço')
                                    ->options(self::getItensImpermeabilizacaoOptions())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            $item = TabelaPreco::find($state);
                                            if ($item) {
                                                $set('descricao_item', $item->nome_item);
                                                $set('unidade_medida', $item->unidade_medida);
                                                $set('valor_unitario', $item->preco_vista);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('descricao_item')
                                    ->label('Descrição')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\Select::make('unidade_medida')
                                    ->label('Unidade')
                                    ->options([
                                        'unidade' => 'UN',
                                        'm2' => 'M²',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('quantidade')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('valor_unitario')
                                    ->label('Valor Unit.')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('subtotal_item')
                                    ->label('Subtotal')
                                    ->content(function (Get $get): string {
                                        $qtd = (float) ($get('quantidade') ?? 0);
                                        $valor = (float) ($get('valor_unitario') ?? 0);
                                        $subtotal = $qtd * $valor;

                                        return 'R$ '.number_format($subtotal, 2, ',', '.');
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(6)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->collapsed(false)
                            ->itemLabel(fn (array $state): ?string => $state['descricao_item'] ?? null)
                            ->addActionLabel('➕ Adicionar Item de Impermeabilização')
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action
                                    ->requiresConfirmation()
                            ),
                    ])
                    ->description('Adicione itens de impermeabilização')
                    ->collapsible(),

                // Seção: Valores Totais
                Forms\Components\Section::make('Valores Totais')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('valor_subtotal_calc')
                                    ->label('Subtotal dos Itens')
                                    ->content(function (Get $get): string {
                                        $itensHigi = $get('itens_higienizacao') ?? [];
                                        $itensImper = $get('itens_impermeabilizacao') ?? [];
                                        $total = 0;

                                        foreach ($itensHigi as $item) {
                                            $qtd = (float) ($item['quantidade'] ?? 0);
                                            $valor = (float) ($item['valor_unitario'] ?? 0);
                                            $total += $qtd * $valor;
                                        }

                                        foreach ($itensImper as $item) {
                                            $qtd = (float) ($item['quantidade'] ?? 0);
                                            $valor = (float) ($item['valor_unitario'] ?? 0);
                                            $total += $qtd * $valor;
                                        }

                                        return 'R$ '.number_format($total, 2, ',', '.');
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('desconto_pix_aplicado')
                                    ->label('Aplicar Desconto PIX (10%)')
                                    ->inline(false)
                                    ->live()
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('valor_desconto_calc')
                                    ->label('Desconto')
                                    ->content(function (Get $get): string {
                                        $itensHigi = $get('itens_higienizacao') ?? [];
                                        $itensImper = $get('itens_impermeabilizacao') ?? [];
                                        $subtotal = 0;

                                        foreach ($itensHigi as $item) {
                                            $qtd = (float) ($item['quantidade'] ?? 0);
                                            $valor = (float) ($item['valor_unitario'] ?? 0);
                                            $subtotal += $qtd * $valor;
                                        }

                                        foreach ($itensImper as $item) {
                                            $qtd = (float) ($item['quantidade'] ?? 0);
                                            $valor = (float) ($item['valor_unitario'] ?? 0);
                                            $subtotal += $qtd * $valor;
                                        }

                                        $desconto = $get('desconto_pix_aplicado') ? ($subtotal * 0.10) : 0;

                                        return 'R$ '.number_format($desconto, 2, ',', '.');
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('valor_total_calc')
                                    ->label('VALOR TOTAL')
                                    ->content(function (Get $get): string {
                                        $itensHigi = $get('itens_higienizacao') ?? [];
                                        $itensImper = $get('itens_impermeabilizacao') ?? [];
                                        $subtotal = 0;

                                        foreach ($itensHigi as $item) {
                                            $qtd = (float) ($item['quantidade'] ?? 0);
                                            $valor = (float) ($item['valor_unitario'] ?? 0);
                                            $subtotal += $qtd * $valor;
                                        }

                                        foreach ($itensImper as $item) {
                                            $qtd = (float) ($item['quantidade'] ?? 0);
                                            $valor = (float) ($item['valor_unitario'] ?? 0);
                                            $subtotal += $qtd * $valor;
                                        }

                                        $desconto = $get('desconto_pix_aplicado') ? ($subtotal * 0.10) : 0;
                                        $total = $subtotal - $desconto;

                                        return 'R$ '.number_format($total, 2, ',', '.');
                                    })
                                    ->extraAttributes(['style' => 'font-size: 1.5rem; font-weight: bold; color: #10b981;'])
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('forma_pagamento')
                                    ->label('Forma de Pagamento')
                                    ->options([
                                        'pix' => '💰 PIX',
                                        'dinheiro' => '💵 Dinheiro',
                                        'cartao_credito' => '💳 Cartão de Crédito',
                                        'cartao_debito' => '💳 Cartão de Débito',
                                        'boleto' => '📄 Boleto',
                                        'transferencia' => '🏦 Transferência',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->helperText('Forma de pagamento prevista')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('pix_chave_tipo')
                                    ->label('Chave PIX para Cobrança')
                                    ->options([
                                        'cnpj' => 'CNPJ: 58.794.846/0001-20',
                                        'telefone' => 'Telefone: (16) 99753-9698',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->helperText('Escolha a chave PIX para gerar o QR Code')
                                    ->visible(fn (Get $get) => $get('forma_pagamento') === 'pix')
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state === 'cnpj') {
                                            $set('pix_chave_valor', '58794846000120');
                                        } elseif ($state === 'telefone') {
                                            $set('pix_chave_valor', '5516997539698');
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Hidden::make('pix_chave_valor'),
                            ]),
                    ])
                    ->columns(1),

                // Seção: Observações
                Forms\Components\Section::make('Observações')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações para o Cliente')
                            ->rows(3)
                            ->placeholder('Observações que aparecerão no orçamento...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observacoes_internas')
                            ->label('Observações Internas')
                            ->rows(2)
                            ->placeholder('Anotações internas (não aparecem para o cliente)...')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('documentos')
                            ->label('Anexos do Orçamento')
                            ->multiple()
                            ->directory('orcamentos-documentos')
                            ->visibility('public')
                            ->disk('public')
                            ->image()
                            ->imagePreviewHeight('180')
                            ->panelLayout('grid')
                            ->imageEditor()
                            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1'])
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText('Documentos e fotos relacionados ao orçamento'),
                    ])
                    ->collapsed()
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_orcamento')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cadastro')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Serviço')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'higienizacao' => 'info',
                        'impermeabilizacao' => 'success',
                        'higienizacao_impermeabilizacao' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'higienizacao' => '🧼 Higienização',
                        'impermeabilizacao' => '💧 Impermeabilização',
                        'higienizacao_impermeabilizacao' => '🧼💧 Hig. + Imper.',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable()
                    ->alignment('right')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('data_orcamento')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_validade')
                    ->label('Válido até')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => $record->expirado() ? 'danger' : 'success')
                    ->description(function ($record): string {
                        if ($record->status === 'convertido') {
                            return 'Convertido em OS';
                        }
                        if ($record->expirado()) {
                            return 'Expirado';
                        }
                        $dias = $record->diasRestantes();

                        return $dias > 0 ? "Restam {$dias} dias" : '';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'aprovado' => 'success',
                        'recusado' => 'danger',
                        'expirado' => 'gray',
                        'convertido' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente' => '⏳ Pendente',
                        'aprovado' => '✅ Aprovado',
                        'recusado' => '❌ Recusado',
                        'expirado' => '⌛ Expirado',
                        'convertido' => '🔄 Convertido',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('parceiro.nome')
                    ->label('Parceiro')
                    ->searchable()
                    ->toggleable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('criado_por')
                    ->label('Criado por')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'aprovado' => 'Aprovado',
                        'recusado' => 'Recusado',
                        'expirado' => 'Expirado',
                        'convertido' => 'Convertido',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('tipo_servico')
                    ->label('Tipo de Serviço')
                    ->options([
                        'higienizacao' => 'Higienização',
                        'impermeabilizacao' => 'Impermeabilização',
                        'higienizacao_impermeabilizacao' => 'Higienização + Impermeabilização',
                    ]),

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

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar Orçamento')
                    ->modalDescription('Ao aprovar este orçamento, será criada automaticamente uma Ordem de Serviço, registro na Agenda e lançamento no Financeiro.')
                    ->modalSubmitActionLabel('Sim, Aprovar')
                    ->visible(fn (Orcamento $record): bool => $record->status === 'pendente')
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
                    ->action(function (Orcamento $record, array $data): void {
                        // Apenas marcar o orçamento como aprovado e salvar a data agendada; a lógica de criação
                        // da OS/Agenda/Financeiro é centralizada no Observer (`OrcamentoObserver`).
                        $record->update([
                            'status' => 'aprovado',
                            'aprovado_em' => now(),
                            'data_servico_agendada' => $data['data_servico'],
                            'observacoes_os' => $data['observacoes_os'] ?? $record->observacoes,
                        ]);
                    })
                    ->successNotificationTitle('Orçamento aprovado com sucesso!')
                    ->successNotification(function () {
                        return \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Orçamento Aprovado!')
                            ->body('A Ordem de Serviço, Agenda e Financeiro foram criados automaticamente.')
                            ->send();
                    }),

                Tables\Actions\Action::make('reprovar')
                    ->label('Reprovar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reprovar Orçamento')
                    ->modalDescription('Confirma a reprovação deste orçamento pelo cliente?')
                    ->modalSubmitActionLabel('Sim, Reprovar')
                    ->visible(fn (Orcamento $record): bool => $record->status === 'pendente')
                    ->form([
                        Forms\Components\Textarea::make('motivo_reprovacao')
                            ->label('Motivo da Reprovação')
                            ->rows(3)
                            ->placeholder('Opcional: descreva o motivo da reprovação...'),
                    ])
                    ->action(function (Orcamento $record, array $data): void {
                        $record->update([
                            'status' => 'recusado',
                            'reprovado_em' => now(),
                            'motivo_reprovacao' => $data['motivo_reprovacao'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Orçamento reprovado')
                    ->successNotification(function () {
                        return \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Orçamento Reprovado')
                            ->body('O orçamento foi marcado como recusado.')
                            ->send();
                    }),

                Tables\Actions\Action::make('visualizar_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Orcamento $record): string => route('orcamento.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Orcamento $record): bool => $record->status !== 'convertido'),
                \App\Filament\Actions\DownloadFileAction::make('documentos', 'public')->label('Download'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações do Orçamento')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('numero_orcamento')
                                    ->label('Número'),
                                Infolists\Components\TextEntry::make('data_orcamento')
                                    ->label('Data')
                                    ->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('data_validade')
                                    ->label('Válido até')
                                    ->date('d/m/Y')
                                    ->badge()
                                    ->color(fn ($record): string => $record->expirado() ? 'danger' : 'success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Cliente')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('cliente.nome')
                                    ->label('Nome'),
                                Infolists\Components\TextEntry::make('cliente.celular')
                                    ->label('Celular'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Serviço')
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo_servico')
                            ->label('Tipo de Serviço')
                            ->badge(),
                        Infolists\Components\TextEntry::make('descricao_servico')
                            ->label('Descrição')
                            ->columnSpanFull(),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('area_m2')
                                    ->label('Área')
                                    ->suffix(' m²'),
                                Infolists\Components\TextEntry::make('valor_m2')
                                    ->label('Valor por m²')
                                    ->money('BRL'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Valores')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('valor_subtotal')
                                    ->label('Subtotal')
                                    ->money('BRL'),
                                Infolists\Components\TextEntry::make('valor_desconto')
                                    ->label('Desconto')
                                    ->money('BRL'),
                                Infolists\Components\TextEntry::make('desconto_pix_aplicado')
                                    ->label('Desconto PIX')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? '10% Aplicado' : 'Não aplicado')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                Infolists\Components\TextEntry::make('valor_total')
                                    ->label('Valor Total')
                                    ->money('BRL')
                                    ->size('lg')
                                    ->weight('bold'),
                            ]),
                        Infolists\Components\TextEntry::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->badge(),
                    ]),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('ordemServico.numero_os')
                            ->label('Ordem de Serviço Gerada')
                            ->visible(fn ($record) => $record->ordem_servico_id !== null),
                        Infolists\Components\TextEntry::make('parceiro.nome')
                            ->label('Parceiro')
                            ->visible(fn ($record) => $record->parceiro_id !== null),
                        Infolists\Components\TextEntry::make('numero_pedido_parceiro')
                            ->label('Nº Pedido Parceiro')
                            ->visible(fn ($record) => $record->numero_pedido_parceiro !== null),
                    ]),

                Infolists\Components\Section::make('Observações')
                    ->schema([
                        Infolists\Components\TextEntry::make('observacoes')
                            ->label('Observações para o Cliente')
                            ->columnSpanFull(),
                    ])
                ,

                Infolists\Components\Section::make("Arquivos")
                    ->schema([
                        Infolists\Components\ImageEntry::make("documentos")
                            ->label("Anexos do Orçamento")
                            ->disk("public")
                            ->visibility("public")
                            ->limit(10)
                            ->height(400)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('documentos_list')
                            ->label('Ações de Arquivo')
                            ->html()
                            ->getStateUsing(function ($record) {
                                if (empty($record->documentos)) {
                                    return '';
                                }

                                $entries = [];

                                foreach ((array) data_get($record, 'documentos') as $path) {
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

                                    $entries[] = "<div class='py-1'><a href='{$downloadUrl}' target='_blank' class='text-sm text-blue-600 underline'>{$name}</a> · <a href='{$downloadUrl}?download=1' class='text-sm'>Baixar</a> · <a href='{$deleteUrl}' class='text-sm text-red-600 ml-2' onclick=\"return confirm('Excluir arquivo?')\">Excluir</a></div>";
                                }

                                return implode('', $entries);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make("Observações")
                    ->schema([
                        Infolists\Components\TextEntry::make("observacoes_internas")
                            ->label("Internas")
                            ->columnSpanFull()
                            ->placeholder("Sem observações internas"),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Informações do Sistema')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('criado_por')
                                    ->label('Criado por')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Criado em')
                                    ->dateTime('d/m/Y H:i'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Atualizado em')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ])
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
            'index' => Pages\ListOrcamentos::route('/'),
            'create' => Pages\CreateOrcamento::route('/create'),
            'view' => Pages\ViewOrcamento::route('/{record}'),
            'edit' => Pages\EditOrcamento::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
