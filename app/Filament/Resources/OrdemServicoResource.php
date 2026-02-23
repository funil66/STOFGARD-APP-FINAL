<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdemServicoResource\Pages;
use App\Models\OrdemServico;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Services\OrdemServicoFormService;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('numero_os')
                            ->label('Nº OS (Prévia)')
                            ->default(fn() => OrdemServico::gerarNumeroOS())
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('O número final será gerado automaticamente ao salvar')
                            ->columnSpan(1),

                        Select::make('cadastro_id')
                            ->label('Cliente Final')
                            ->relationship('cliente', 'nome', fn(Builder $query) => $query->where('tipo', 'cliente'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(3)
                            ->createOptionForm(\App\Filament\Resources\CadastroResource::getFormSchema()),

                        Select::make('loja_id')
                            ->label('Loja / Parceiro Indicador')
                            ->options(\App\Models\Cadastro::where('tipo', 'loja')->pluck('nome', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Quem realizou ou indicou a venda?')
                            ->columnSpan(2),

                        Select::make('vendedor_id')
                            ->label('Vendedor Responsável')
                            ->options(\App\Models\Cadastro::where('tipo', 'vendedor')->pluck('nome', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn() => \App\Models\Cadastro::where('tipo', 'vendedor')->where('email', auth()->user()->email)->first()?->id)
                            ->columnSpan(2),

                        Select::make('funcionario_id')
                            ->label('Funcionário Técnico/Executor')
                            ->options(\App\Models\Cadastro::where('tipo', 'funcionario')->pluck('nome', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Quem realizou o serviço?')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('id_parceiro')
                            ->label('ID Parceiro')
                            ->placeholder('Identificação da loja/vendedor')
                            ->maxLength(255)
                            ->columnSpan(2),
                    ])->columns(['default' => 1, 'sm' => 2, 'lg' => 4]),

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
                                        ->afterStateUpdated(fn(Forms\Set $set, $state) => OrdemServicoFormService::atualizarDadosServico($set, $state)),

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
                                ])->columns(['default' => 1, 'sm' => 2]),

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
                                                OrdemServicoFormService::atualizarItem($set, $get, $state);
                                                self::recalcularTotal($set, $get);
                                            })
                                            ->columnSpan(4),

                                        TextInput::make('quantidade')
                                            ->numeric()
                                            ->default(1)
                                            ->label('Qtd')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                OrdemServicoFormService::recalcularSubtotal($set, $get);
                                                self::recalcularTotal($set, $get);
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('valor_unitario')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->label('Unit.')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                OrdemServicoFormService::recalcularSubtotal($set, $get);
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
                                    ->columns(['default' => 1, 'md' => 7])
                                    ->live()
                                    ->afterStateUpdated(fn(Forms\Set $set, Forms\Get $get) => self::recalcularTotal($set, $get)),

                                TextInput::make('valor_total')
                                    ->label('TOTAL GERAL')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'text-xl font-bold']),

                                Textarea::make('observacoes')
                                    ->label('Observações (do Orçamento)')
                                    ->rows(3)
                                    ->placeholder('Observações transferidas do orçamento ou notas da OS...')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Datas e Prazos')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                DatePicker::make('data_abertura')->label('Data Venda')->default(now())->required(),
                                Forms\Components\DateTimePicker::make('data_prevista')->label('Data Agendada')->minDate(now()),
                                DatePicker::make('data_conclusao')->label('Conclusão'),
                                TextInput::make('dias_garantia')->label('Garantia (Dias)')->numeric()->default(90),
                            ])->columns(['default' => 1, 'sm' => 2, 'lg' => 4]),

                        Tab::make('Evidências')
                            ->icon('heroicon-o-camera')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('fotos_antes')->label('Antes')->multiple()->disk('public')->directory('os-fotos'),
                                SpatieMediaLibraryFileUpload::make('fotos_depois')->label('Depois')->multiple()->disk('public')->directory('os-fotos'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('📦 Produtos do Estoque')
                    ->description('Selecione produtos do estoque que serão utilizados nesta OS (opcional)')
                    ->icon('heroicon-o-beaker')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('produtos_selecionados') // Renomeado para evitar conflito e salvar manualmente
                            ->label('')
                            ->schema([
                                Select::make('estoque_id')
                                    ->label('Produto')
                                    ->options(
                                        fn() => \App\Models\Estoque::query()
                                            ->orderBy('item')
                                            ->get()
                                            ->mapWithKeys(fn($e) => [
                                                $e->id => $e->item . ' (Disponível: ' . number_format($e->quantidade, 2, ',', '.') . ' ' . $e->unidade . ')',
                                            ])
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->afterStateUpdated(fn($state, Forms\Set $set) => OrdemServicoFormService::atualizarEstoque($set, $state))
                                    ->columnSpan(3),

                                TextInput::make('disponivel')
                                    ->label('Disponível')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix(fn(Forms\Get $get) => $get('unidade') ?? '')
                                    ->columnSpan(1),

                                TextInput::make('quantidade_utilizada')
                                    ->label('Quantidade a Utilizar')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->default(1)
                                    ->suffix(fn(Forms\Get $get) => $get('unidade') ?? '')
                                    ->helperText('Quantidade que será deduzida do estoque')
                                    ->columnSpan(2),

                                Hidden::make('unidade'),

                                Textarea::make('observacao')
                                    ->label('Observação')
                                    ->placeholder('Ex: Produto aplicado na peça X')
                                    ->maxLength(500)
                                    ->columnSpanFull()
                                    ->rows(2),
                            ])
                            ->columns(['default' => 1, 'md' => 6])
                            ->defaultItems(0)
                            ->addActionLabel('➕ Adicionar Produto do Estoque')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string => \App\Models\Estoque::find($state['estoque_id'] ?? 0)?->item ?? 'Produto'
                            ),
                    ]),

                Section::make('Central de Arquivos')
                    ->description('Envie fotos, documentos e comprovantes (Máx: 20MB).')
                    ->icon('heroicon-o-paper-clip')
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

    // --- FUNÇÃO DE CÁLCULO ---
    public static function recalcularTotal(Forms\Set $set, Forms\Get $get): void
    {
        OrdemServicoFormService::recalcularTotal($set, $get);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_os')
                    ->label('OS')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn($record) => $record->cliente?->nome ?? '-')
                    ->icon('heroicon-o-clipboard-document-check'),

                Tables\Columns\TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->url(fn($record) => \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $record->cadastro_id]))
                    ->color('primary')
                    ->weight('bold')
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('loja.nome')
                    ->label('Loja')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->searchable()
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('vendedor.nome')
                    ->label('Vendedor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('id_parceiro')
                    ->label('ID Parceiro')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'aberta' => '📂',
                        'agendada' => '📅',
                        'concluida' => '✓',
                        'cancelada' => '✗',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'aberta' => 'info',
                        'agendada' => 'warning',
                        'concluida' => 'success',
                        'cancelada' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('tem_garantia')
                    ->label('')
                    ->tooltip(fn(?OrdemServico $record): string => $record?->status_garantia === 'ativa'
                        ? 'Garantia ativa até ' . ($record->data_fim_garantia?->format('d/m/Y') ?? '')
                        : ($record?->status_garantia === 'vencida' ? 'Garantia vencida' : 'Sem garantia'))
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn(?OrdemServico $record): bool => $record?->status_garantia === 'ativa'),

                Tables\Columns\TextColumn::make('valor_total')
                    ->money('BRL')
                    ->label('Total')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado')
                    ->date('d/m')
                    ->sortable()
                    ->visibleFrom('lg'),
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
                        Forms\Components\DatePicker::make('created_from')->label('Criado de'),
                        Forms\Components\DatePicker::make('created_until')->label('Criado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn(Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn(Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions(
                \App\Support\Filament\StofgardTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        // 1. PDF (Verde)
                        Tables\Actions\Action::make('pdf')
                            ->label('PDF')
                            ->icon('heroicon-s-document-text')
                            ->color('success')
                            ->tooltip('Imprimir Ficha da OS')
                            // ->iconButton()
                            ->url(fn(?OrdemServico $record) => $record ? route('os.pdf', $record) : null)
                            ->openUrlInNewTab(),

                        // 4. Baixar/Receber (Verde escuro)
                        Tables\Actions\Action::make('baixar')
                            ->label('Receber')
                            ->tooltip('Receber Pagamento')
                            ->icon('heroicon-s-currency-dollar')
                            ->color('success')
                            // ->iconButton()
                            ->visible(fn(OrdemServico $record) => $record->status !== 'cancelada' && ($record->financeiro?->status !== 'pago'))
                            ->form([
                                Forms\Components\DatePicker::make('data_pagamento')->default(now())->required()->label('Data do Pagamento'),
                                Forms\Components\TextInput::make('valor_pago')
                                    ->default(fn(OrdemServico $record) => $record->valor_total)
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->label('Valor Recebido'),
                                Forms\Components\Select::make('forma_pagamento')
                                    ->options([
                                        'pix' => 'PIX',
                                        'dinheiro' => 'Dinheiro',
                                        'cartao_credito' => 'Crédito',
                                        'cartao_debito' => 'Débito'
                                    ])
                                    ->required()
                                    ->label('Forma de Pagamento'),
                            ])
                            ->action(function (OrdemServico $record, array $data) {
                                $financeiro = $record->financeiro;
                                if (!$financeiro) {
                                    $financeiro = \App\Models\Financeiro::create([
                                        'cadastro_id' => $record->cadastro_id,
                                        'ordem_servico_id' => $record->id,
                                        'orcamento_id' => $record->orcamento_id,
                                        'id_parceiro' => $record->id_parceiro,
                                        'tipo' => 'entrada',
                                        'descricao' => "Recebimento OS #{$record->numero_os}",
                                        'valor' => $record->valor_total,
                                        'data_vencimento' => $record->data_conclusao ?? now(),
                                        'data' => now(),
                                        'status' => 'pendente',
                                    ]);
                                }
                                // Usa o FinanceiroService para garantir lógica de pagamento (parcial ou total)
                                \App\Services\FinanceiroService::baixarPagamento($financeiro, $data);
                            }),

                        // 3. Concluir OS
                        Tables\Actions\Action::make('concluir')
                            ->label('Concluir')
                            ->tooltip('Concluir OS')
                            ->icon('heroicon-s-check-circle') // Changed Icon to check-circle
                            ->color('warning')
                            // ->iconButton()
                            ->visible(fn(?OrdemServico $record) => $record && $record->status !== 'concluida')
                            ->requiresConfirmation()
                            ->modalHeading('Concluir Ordem de Serviço')
                            ->modalDescription('Tem certeza que deseja marcar esta OS como concluída?')
                            ->action(fn(OrdemServico $record) => $record->update(['status' => 'concluida'])),
                    ]
                )
            )
            ->bulkActions(
                \App\Support\Filament\StofgardTable::defaultBulkActions([
                    Tables\Actions\BulkAction::make('marcar_agendada')
                        ->label('Marcar como Agendada')
                        ->icon('heroicon-m-calendar')
                        ->action(fn($records) => $records->each->update(['status' => 'agendada'])),
                    Tables\Actions\BulkAction::make('marcar_concluida')
                        ->label('Marcar como Concluída')
                        ->icon('heroicon-m-check-circle')
                        ->action(fn($records) => $records->each->update(['status' => 'concluida'])),
                ])
            );
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
                                ->weight('bold')
                                ->url(fn($record) => \App\Filament\Resources\CadastroResource::getUrl('view', ['record' => $record->cadastro_id])),
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
                            TextEntry::make('id_parceiro')
                                ->label('ID Parceiro')
                                ->badge()
                                ->color('info')
                                ->placeholder('-'),
                        ]),

                        InfolistGrid::make(1)->schema([
                            TextEntry::make('endereco_cliente')
                                ->label('📍 Local do Serviço (Endereço do Cliente)')
                                ->state(fn($record) => $record->cliente ? "{$record->cliente->logradouro}, {$record->cliente->numero} - {$record->cliente->bairro}, {$record->cliente->cidade}/{$record->cliente->estado}" : 'Endereço não cadastrado')
                                ->url(fn($state) => $state !== 'Endereço não cadastrado' ? "https://www.google.com/maps/search/?api=1&query=" . urlencode($state) : null, true)
                                ->color('primary')
                                ->weight('bold')
                                ->icon('heroicon-m-map')
                                ->columnSpanFull(),
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
                            TextEntry::make('valor_desconto')
                                ->label('🎁 Desconto')
                                ->money('BRL')
                                ->color('warning')
                                ->placeholder('R$ 0,00'),
                            TextEntry::make('data_prevista')
                                ->label('📅 Data Agendada')
                                ->dateTime('d/m/Y H:i')
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
                                })
                                ->helperText(function ($record) {
                                    if ($record->status_garantia !== 'ativa' || !$record->data_fim_garantia) {
                                        return null;
                                    }
                                    $dias = now()->diffInDays($record->data_fim_garantia, false);
                                    $dias = (int) $dias; // Ensure integer
                        
                                    if ($dias < 0) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<span class="text-danger-600 font-bold animate-pulse">🔴 Vencida há ' . abs($dias) . ' dias</span>'
                                        );
                                    }
                                    if ($dias === 0) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<span class="text-warning-600 font-bold animate-pulse">⚠️ Vence hoje!</span>'
                                        );
                                    }
                                    return new \Illuminate\Support\HtmlString(
                                        '<span class="text-success-600 font-bold animate-pulse">⏳ Restam ' . $dias . ' dias</span>'
                                    );
                                }),
                        ]),
                        TextEntry::make('observacoes')
                            ->label('📝 Observações')
                            ->placeholder('Nenhuma observação registrada')
                            ->columnSpanFull()
                            ->visible(fn($record) => !empty($record->observacoes)),
                    ])
                    ->collapsible(),

                // ===== ABAS DE DETALHES =====
                Infolists\Components\Tabs::make('Detalhes')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('🛠️ Serviços e Itens')
                            ->schema([
                                RepeatableEntry::make('itens')
                                    ->label('')
                                    ->schema([
                                        InfolistGrid::make(4)->schema([
                                            TextEntry::make('descricao')->label('Item')->weight('bold'),
                                            TextEntry::make('quantidade')->label('Qtd')->alignCenter(),
                                            TextEntry::make('valor_unitario')->label('Unit.')->money('BRL'),
                                            TextEntry::make('subtotal')->label('Total')->money('BRL')->weight('bold')->color('success'),
                                        ]),
                                    ])
                                    ->grid(1),
                                TextEntry::make('descricao_servico')
                                    ->label('Descrição Técnica')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ]),

                        Infolists\Components\Tabs\Tab::make('📦 Produtos Utilizados')
                            ->badge(fn(OrdemServico $record) => $record->produtosUtilizados()->count())
                            ->schema([
                                RepeatableEntry::make('produtosUtilizados')
                                    ->label('')
                                    ->schema([
                                        InfolistGrid::make(3)->schema([
                                            TextEntry::make('item')->label('Produto')->weight('bold'),
                                            TextEntry::make('pivot.quantidade_utilizada')
                                                ->label('Qtd')
                                                ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . $record->pivot->unidade),
                                            TextEntry::make('pivot.observacao')->label('Obs'),
                                        ]),
                                    ])
                                    ->grid(1),
                            ]),

                        Infolists\Components\Tabs\Tab::make('📸 Evidências e Arquivos')
                            ->schema([
                                InfolistGrid::make(2)->schema([
                                    Infolists\Components\SpatieMediaLibraryImageEntry::make('fotos_antes')
                                        ->label('Fotos Antes')
                                        ->collection('fotos_antes')
                                        ->disk('public')
                                        ->columnSpan(1),

                                    Infolists\Components\SpatieMediaLibraryImageEntry::make('fotos_depois')
                                        ->label('Fotos Depois')
                                        ->collection('fotos_depois')
                                        ->disk('public')
                                        ->columnSpan(1),
                                ]),

                                InfolistSection::make('Documentos e Arquivos')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('arquivos_list')
                                            ->label('Lista de Arquivos')
                                            ->html()
                                            ->getStateUsing(function ($record) {
                                                $files = $record->getMedia('arquivos');
                                                if ($files->isEmpty())
                                                    return '<span class="text-gray-500 text-sm">Nenhum arquivo anexado.</span>';

                                                $html = '<ul class="list-disc pl-4 space-y-1">';
                                                foreach ($files as $file) {
                                                    $url = $file->getUrl();
                                                    $name = $file->file_name;
                                                    $size = $file->human_readable_size;
                                                    $html .= "<li><a href='{$url}' target='_blank' class='text-primary-600 hover:underline'>{$name}</a> <span class='text-xs text-gray-500'>({$size})</span></li>";
                                                }
                                                $html .= '</ul>';
                                                return $html;
                                            }),
                                    ])
                                    ->collapsible(),
                            ]),

                        Infolists\Components\Tabs\Tab::make('💰 Financeiro')
                            ->schema([
                                InfolistGrid::make(3)->schema([
                                    TextEntry::make('financeiro.status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn($state) => $state === 'pago' ? 'success' : 'warning'),
                                    TextEntry::make('financeiro.data_pagamento')->label('Data Pagto')->date('d/m/Y'),
                                    TextEntry::make('financeiro.valor_pago')->label('Valor Pago')->money('BRL'),
                                ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('📜 Histórico')
                            ->icon('heroicon-m-clock')
                            ->badge(fn($record) => $record->audits()->count())
                            ->schema([
                                RepeatableEntry::make('audits')
                                    ->label('')
                                    ->schema([
                                        InfolistGrid::make(4)->schema([
                                            TextEntry::make('user.name')
                                                ->label('Usuário')
                                                ->icon('heroicon-m-user'),
                                            TextEntry::make('event')
                                                ->label('Ação')
                                                ->badge()
                                                ->formatStateUsing(fn(string $state): string => match ($state) {
                                                    'created' => 'Criação',
                                                    'updated' => 'Edição',
                                                    'deleted' => 'Exclusão',
                                                    'restored' => 'Restauração',
                                                    default => ucfirst($state),
                                                })
                                                ->color(fn(string $state): string => match ($state) {
                                                    'created' => 'success',
                                                    'updated' => 'warning',
                                                    'deleted' => 'danger',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('created_at')
                                                ->label('Data')
                                                ->dateTime('d/m/Y H:i:s'),
                                            TextEntry::make('ip_address')
                                                ->label('IP')
                                                ->icon('heroicon-m-globe-alt')
                                                ->copyable(),
                                        ]),
                                        \Filament\Infolists\Components\KeyValueEntry::make('old_values')
                                            ->label('Valores Antigos')
                                            ->visible(fn($state) => !empty($state)),
                                        \Filament\Infolists\Components\KeyValueEntry::make('new_values')
                                            ->label('Novos Valores')
                                            ->visible(fn($state) => !empty($state)),
                                    ])
                                    ->grid(1)
                                    ->contained(false),
                                TextEntry::make('sem_historico')
                                    ->label('')
                                    ->default('Nenhuma alteração registrada.')
                                    ->visible(fn($record) => $record->audits()->count() === 0),
                            ]),
                    ]),
            ]);
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
