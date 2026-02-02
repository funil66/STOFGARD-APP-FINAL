<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdemServicoResource\Pages;
use App\Models\OrdemServico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\RepeatableEntry;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Ordens de Serviço';
    protected static ?string $modelLabel = 'Ordem de Serviço';
    protected static ?string $navigationGroup = 'Operacional';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identificação e Origem')
                    ->description('Defina o cliente tomador e a origem comercial da venda.')
                    ->schema([
                        TextInput::make('numero_os')
                            ->label('Nº OS (Prévia)')
                            ->default(fn() => OrdemServico::gerarNumeroOS())
                            ->disabled()
                            ->dehydrated(false) // Don't include in form data - will be generated server-side
                            ->helperText('O número final será gerado automaticamente ao salvar')
                            ->required(false) // Not required since it won't be submitted
                            ->columnSpan(1),

                        Select::make('cadastro_id')
                            ->label('Cliente Final')
                            ->relationship('cliente', 'nome', fn(Builder $query) => $query->where('tipo', 'cliente'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(3)
                            ->createOptionForm([
                                TextInput::make('nome')->required(),
                                TextInput::make('celular')->mask('(99) 99999-9999'),
                                Select::make('tipo')->options(['cliente' => 'Cliente'])->default('cliente')->hidden(),
                            ]),

                        Select::make('loja_id')
                            ->label('Loja / Parceiro Indicador')
                            ->options(\App\Models\Cadastro::where('tipo', 'loja')->pluck('nome', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Quem realizou ou indicou a venda?')
                            ->columnSpan(2),

                        Select::make('vendedor_id')
                            ->label('Vendedor Responsável')
                            ->options(\App\Models\Cadastro::where('tipo', 'vendedor')->pluck('nome', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => \App\Models\Cadastro::where('tipo', 'vendedor')->where('email', auth()->user()->email)->first()?->id)
                            ->columnSpan(2),
                    ])->columns(4),

                Tabs::make('Detalhes da Operação')
                    ->tabs([
                        Tab::make('Serviços e Valores')
                            ->icon('heroicon-o-wrench')
                            ->schema([
                                Group::make()->schema([
                                    Select::make('tipo_servico')
                                        ->label('Serviço Principal')
                                        ->options(\App\Services\ServiceTypeManager::getOptions())
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            // Busca dados dinamicamente da tabela de preços
                                            $servico = \App\Models\TabelaPreco::where('tipo_servico', $state)
                                                ->whereNotNull('descricao_tecnica')
                                                ->first();

                                            if ($servico) {
                                                $set('descricao_servico', $servico->descricao_tecnica);
                                                $set('dias_garantia', $servico->dias_garantia);
                                            }
                                        }),

                                    Select::make('status')
                                        ->options([
                                            'aberta' => 'Aberta',
                                            'agendada' => 'Agendada',
                                            'concluida' => 'Concluída',
                                            'cancelada' => 'Cancelada',
                                        ])
                                        ->default('aberta')
                                        ->required(),

                                    Forms\Components\KeyValue::make('extra_attributes')
                                        ->label('Detalhes Adicionais (Personalizado)')
                                        ->keyLabel('Campo (Ex: Tecido, Cor)')
                                        ->valueLabel('Valor')
                                        ->reorderable()
                                        ->columnSpanFull(),
                                ])->columns(2),

                                Textarea::make('descricao_servico')
                                    ->label('Descrição Técnica (Texto do Orçamento)')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Repeater::make('itens')
                                    ->relationship('itens')
                                    ->label('Itens do Serviço (Sofá, Cadeira, etc)')
                                    ->schema([
                                        Select::make('descricao')
                                            ->label('Item / Serviço')
                                            ->options(fn() => \App\Models\TabelaPreco::where('ativo', true)->pluck('nome_item', 'nome_item'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                $item = \App\Models\TabelaPreco::where('nome_item', $state)->first();
                                                if ($item) {
                                                    $set('unidade_medida', $item->unidade_medida);
                                                    // Define preço padrão se houver (Preço Base ou Higienização como padrão)
                                                    $set('valor_unitario', $item->preco_vista);
                                                }
                                                // Recalcula linha
                                                $qtd = (float) $get('quantidade') ?: 1;
                                                $unit = (float) $get('valor_unitario') ?: 0;
                                                $set('subtotal', $qtd * $unit);

                                                self::recalcularTotal($set, $get);
                                            })
                                            ->columnSpan(4),

                                        TextInput::make('quantidade')
                                            ->numeric()
                                            ->default(1)
                                            ->label('Qtd')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                $set('subtotal', (float) $get('quantidade') * (float) $get('valor_unitario'));
                                                self::recalcularTotal($set, $get);
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('valor_unitario')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->label('Unit.')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                $set('subtotal', (float) $get('quantidade') * (float) $get('valor_unitario'));
                                                self::recalcularTotal($set, $get);
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('subtotal')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->label('Total')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(1),

                                        Forms\Components\Hidden::make('unidade_medida'),
                                    ])
                                    ->columns(7)
                                    ->live()
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get)),

                                TextInput::make('valor_total')
                                    ->label('TOTAL GERAL')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'text-xl font-bold']),
                            ]),

                        Tab::make('Datas e Prazos')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                DatePicker::make('data_abertura')->label('Data Venda')->default(now())->required(),
                                DatePicker::make('data_prevista')->label('Data Agendada'),
                                DatePicker::make('data_conclusao')->label('Conclusão'),
                                TextInput::make('dias_garantia')->label('Garantia (Dias)')->numeric(),
                            ])->columns(4),

                        Tab::make('Evidências')
                            ->icon('heroicon-o-camera')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('fotos_antes')->label('Antes')->multiple()->disk('public')->directory('os-fotos'),
                                SpatieMediaLibraryFileUpload::make('fotos_depois')->label('Depois')->multiple()->disk('public')->directory('os-fotos'),
                            ]),
                    ])->columnSpanFull(),
                Section::make('Central de Arquivos')
                    ->description('Envie fotos, documentos e comprovantes (Máx: 20MB).')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('arquivos')
                            ->label('Arquivos e Mídia')
                            ->collection('arquivos')
                            ->multiple()
                            ->disk('public')
                            ->maxSize(20480)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_os')
                    ->label('OS')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('loja.nome')
                    ->label('Loja')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aberta' => 'info',
                        'agendada' => 'warning',
                        'concluida' => 'success',
                        'cancelada' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_garantia')
                    ->label('Garantia')
                    ->badge()
                    ->color(fn(string $state): string => \App\Services\ServiceTypeManager::getColor($state))
                    ->formatStateUsing(fn(string $state): string => \App\Services\ServiceTypeManager::getLabel($state))
                    ->sortable()
                    ->formatStateUsing(fn(?OrdemServico $record, string $state): string => match ($state) {
                        'ativa' => '✅ Até ' . ($record->data_fim_garantia?->format('d/m/Y') ?? ''),
                        'vencida' => '🔴 Venceu em ' . ($record->data_fim_garantia?->format('d/m/Y') ?? ''),
                        'pendente' => '🕒 Aguardando Conclusão',
                        default => '-',
                    })
                    ->visible(fn(?OrdemServico $record) => $record && $record->dias_garantia > 0),

                Tables\Columns\TextColumn::make('valor_total')
                    ->money('BRL')
                    ->label('Total')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aberta' => 'Aberta',
                        'agendada' => 'Agendada',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada',
                    ]),

                Tables\Filters\SelectFilter::make('loja_id')
                    ->label('Loja')
                    ->relationship('loja', 'nome'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Criado de'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Criado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('receber')
                    ->label('Receber')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn(OrdemServico $record) => $record->status !== 'cancelada' && ($record->financeiro?->status !== 'pago'))
                    ->form([
                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data do Pagamento')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('valor_pago')
                            ->label('Valor Recebido (R$)')
                            ->default(fn(OrdemServico $record) => $record->valor_total)
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                        Forms\Components\Select::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->options([
                                'pix' => 'PIX',
                                'dinheiro' => 'Dinheiro',
                                'cartao_credito' => 'Cartão de Crédito',
                                'cartao_debito' => 'Cartão de Débito',
                                'boleto' => 'Boleto',
                            ])
                            ->required(),
                    ])
                    ->action(function (OrdemServico $record, array $data) {
                        $financeiro = $record->financeiro;

                        // Se não existir financeiro, cria um (Safety Net)
                        if (!$financeiro) {
                            $financeiro = \App\Models\Financeiro::create([
                                'cadastro_id' => $record->cliente_id ?? null, // Usa cliente_id se cadastro_id for nulo
                                'ordem_servico_id' => $record->id,
                                'tipo' => 'entrada',
                                'descricao' => "Recebimento OS #{$record->numero_os}",
                                'valor' => $record->valor_total,
                                'data_vencimento' => $record->data_conclusao ?? now(),
                                'status' => 'pendente',
                            ]);
                        }

                        // Atualiza o Financeiro
                        $financeiro->update([
                            'status' => 'pago',
                            'valor_pago' => $data['valor_pago'],
                            'data_pagamento' => $data['data_pagamento'],
                            'forma_pagamento' => $data['forma_pagamento'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Pagamento Registrado!')
                            ->body("O financeiro foi atualizado com sucesso.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('concluir')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(?OrdemServico $record) => $record && $record->status !== 'concluida')
                    ->requiresConfirmation()
                    ->action(function (OrdemServico $record) {
                        $record->update(['status' => 'concluida']);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('OS Concluída!')
                            ->send();
                    }),

                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn(?OrdemServico $record) => $record ? route('os.pdf', $record) : null)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(OrdemServico $record) => route('os.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('marcar_agendada')
                        ->label('Marcar como Agendada')
                        ->icon('heroicon-o-calendar')
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['status' => 'agendada'])),

                    Tables\Actions\BulkAction::make('marcar_concluida')
                        ->label('Marcar como Concluída')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'concluida'])),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO DA OS =====
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('numero_os')
                                ->label('Número da OS')
                                ->weight('bold')
                                ->columnSpan(1)
                                ->size(TextEntry\TextEntrySize::Large)
                                ->copyable(),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'concluida' => 'success',
                                    'cancelada' => 'danger',
                                    'agendada' => 'warning',
                                    'aberta' => 'info',
                                    default => 'gray',
                                }),
                            TextEntry::make('tipo_servico')
                                ->label('Tipo de Serviço')
                                ->badge()
                                ->color('primary'),
                            TextEntry::make('data_abertura')
                                ->label('Data Abertura')
                                ->date('d/m/Y'),
                        ]),
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('cliente.nome')
                                ->label('Cliente')
                                ->icon('heroicon-m-user')
                                ->weight('bold'),
                            TextEntry::make('cliente.telefone')
                                ->label('WhatsApp')
                                ->icon('heroicon-m-chat-bubble-left-right')
                                ->url(fn($state) => $state ? 'https://wa.me/55' . preg_replace('/\D/', '', $state) : null, true),
                            TextEntry::make('loja.nome')
                                ->label('Loja Parceira')
                                ->icon('heroicon-m-building-storefront'),
                            TextEntry::make('vendedor.nome')
                                ->label('Vendedor')
                                ->icon('heroicon-m-user-circle'),
                        ]),
                    ]),

                // ===== RESUMO DE VALORES =====
                InfolistSection::make('💰 Resumo Financeiro')
                    ->schema([
                        InfolistGrid::make(4)->schema([
                            TextEntry::make('valor_total')
                                ->label('💵 Valor Total')
                                ->money('BRL')
                                ->color('success')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('data_prevista')
                                ->label('📅 Data Agendada')
                                ->date('d/m/Y')
                                ->color('warning'),
                            TextEntry::make('data_conclusao')
                                ->label('✅ Conclusão')
                                ->date('d/m/Y')
                                ->color('success')
                                ->placeholder('Não concluída'),
                            TextEntry::make('status_garantia')
                                ->label('🛡️ Garantia')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'ativa' => 'success',
                                    'vencida' => 'danger',
                                    'pendente' => 'warning',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'ativa' => 'Ativa',
                                    'vencida' => 'Vencida',
                                    'pendente' => 'Aguardando Conclusão',
                                    'nenhuma' => 'Sem garantia',
                                    default => '-',
                                }),
                        ]),
                    ])
                    ->collapsible(),

                // ===== ITENS DO SERVIÇO =====
                InfolistSection::make('🛠️ Itens do Serviço')
                    ->schema([
                        RepeatableEntry::make('itens')
                            ->label('')
                            ->schema([
                                InfolistGrid::make(5)->schema([
                                    TextEntry::make('descricao')
                                        ->label('Item')
                                        ->weight('bold')
                                        ->columnSpan(2),
                                    TextEntry::make('quantidade')
                                        ->label('Qtd')
                                        ->alignCenter(),
                                    TextEntry::make('valor_unitario')
                                        ->label('Unit.')
                                        ->money('BRL'),
                                    TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->money('BRL')
                                        ->weight('bold')
                                        ->color('success'),
                                ]),
                            ])
                            ->grid(1),
                    ])
                    ->collapsible(),

                // ===== DESCRIÇÃO E OBSERVAÇÕES =====
                InfolistSection::make('📝 Descrição do Serviço')
                    ->schema([
                        TextEntry::make('descricao_servico')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ===== ABAS DE HISTÓRICO =====
                Infolists\Components\Tabs::make('Informações Adicionais')
                    ->tabs([
                        // ABA: FINANCEIRO
                        Infolists\Components\Tabs\Tab::make('💰 Financeiro')
                            ->schema([
                                InfolistGrid::make(3)->schema([
                                    TextEntry::make('financeiro.status')
                                        ->label('Status Pagamento')
                                        ->badge()
                                        ->color(fn($state) => match ($state) {
                                            'pago' => 'success',
                                            'cancelado' => 'danger',
                                            default => 'warning',
                                        }),
                                    TextEntry::make('financeiro.data')
                                        ->label('Data Pagamento')
                                        ->date('d/m/Y'),
                                    TextEntry::make('financeiro.valor')
                                        ->label('Valor')
                                        ->money('BRL')
                                        ->weight('bold'),
                                ]),
                                Infolists\Components\TextEntry::make('empty_financeiro')
                                    ->label('')
                                    ->default('Nenhum registro financeiro vinculado.')
                                    ->visible(fn($record) => !$record->financeiro),
                            ]),

                        // ABA: AGENDA
                        Infolists\Components\Tabs\Tab::make('📅 Agendamento')
                            ->schema([
                                InfolistGrid::make(3)->schema([
                                    TextEntry::make('data_prevista')
                                        ->label('Data/Hora Prevista')
                                        ->dateTime('d/m/Y H:i'),
                                    TextEntry::make('data_conclusao')
                                        ->label('Conclusão')
                                        ->date('d/m/Y'),
                                    TextEntry::make('dias_garantia')
                                        ->label('Garantia (dias)')
                                        ->suffix(' dias'),
                                ]),
                            ]),

                        // ABA: ARQUIVOS
                        Infolists\Components\Tabs\Tab::make('📎 Arquivos')
                            ->schema([
                                Infolists\Components\TextEntry::make('arquivos_info')
                                    ->label('')
                                    ->default('Para gerenciar arquivos, use o modo de edição.')
                                    ->helperText('Clique em "Editar" para adicionar ou remover arquivos.'),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrdemServicos::route('/'),
            'create' => Pages\CreateOrdemServico::route('/create'),
            'edit' => Pages\EditOrdemServico::route('/{record}/edit'),
            'view' => Pages\ViewOrdemServico::route('/{record}'),
        ];
    }

    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        // Soma os subtotais do Repeater
        $itens = $get('itens');
        $total = 0;

        if (is_array($itens)) {
            foreach ($itens as $item) {
                $subtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : 0;
                $total += $subtotal;
            }
        }

        $set('valor_total', $total);
    }
}