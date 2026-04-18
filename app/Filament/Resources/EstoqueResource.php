<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstoqueResource\Pages;
use App\Models\Estoque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EstoqueResource extends Resource
{
    protected static ?string $model = Estoque::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Estoque';

    protected static ?string $modelLabel = 'Item de Estoque';

    protected static ?string $pluralModelLabel = 'Estoque';

    // Submódulo do Almoxarifado
    protected static ?string $slug = 'almoxarifado/estoques';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Dados do Produto')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Forms\Components\TextInput::make('item')
                                    ->label('Nome do Produto')
                                    ->placeholder('Ex: Impermeabilizante')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('observacoes')
                                    ->label('Descrição / Observações')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Precificação')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                                    ->schema([
                                        Forms\Components\TextInput::make('preco_interno')
                                            ->label('Custo Interno (R$/Unid)')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->placeholder('0,00'),

                                        Forms\Components\TextInput::make('preco_venda')
                                            ->label('Preço Venda (R$/Unid)')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->placeholder('0,00'),
                                    ]),
                            ]),
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Controle de Estoque')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Forms\Components\TextInput::make('quantidade')
                                    ->label('Estoque Atual')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),

                                Forms\Components\Select::make('unidade')
                                    ->label('Unidade de Medida')
                                    ->options(fn() => \App\Models\Categoria::where('tipo', 'estoque_unidade')
                                        ->where('ativo', true)
                                        ->pluck('nome', 'slug'))
                                    ->default('unidade')
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('minimo_alerta')
                                    ->label('Estoque Mínimo')
                                    ->numeric()
                                    ->default(10)
                                    ->helperText('Alerta quando atingir este nível'),

                                Forms\Components\Select::make('local_estoque_id')
                                    ->label('Almoxarifado / Local')
                                    ->relationship('localEstoque', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('Opcional. Ex: Loja Matriz, Carrinha do Técnico.'),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-cube')
                    ->description(fn(Estoque $record) => $record->observacoes ? \Illuminate\Support\Str::limit($record->observacoes, 30) : null),

                Tables\Columns\TextColumn::make('localEstoque.nome')
                    ->label('Local')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantidade')
                    ->label('Estoque')
                    ->sortable()
                    ->badge()
                    ->color(fn(Estoque $record): string => $record->cor)
                    ->formatStateUsing(fn($state, Estoque $record) => $state . ' ' . $record->unidade),

                Tables\Columns\TextColumn::make('preco_interno')
                    ->label('Custo')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('preco_venda')
                    ->label('Venda')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Valor Total em Estoque (Quantidade * Custo)
                Tables\Columns\TextColumn::make('total_custo')
                    ->label('Total Custo')
                    ->state(fn(Estoque $record) => $record->quantidade * $record->preco_interno)
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('status')
                    ->label('Situação')
                    ->state(fn(Estoque $record): bool => !$record->isAbaixoDoMinimo())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('item')
            ->actions(
                \App\Support\Filament\AutonomiaTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        Tables\Actions\Action::make('pdf')
                            ->label('Gerar PDF')
                            ->icon('heroicon-o-document-text')
                            ->color('success')
                            ->tooltip('Gerar PDF em fila')
                            ->url(fn(Estoque $record) => route('estoque.pdf', $record)),

                        Tables\Actions\Action::make('adicionar')
                            ->label('Entrada Rápida')
                            ->tooltip('Entrada Rápida')
                            ->icon('heroicon-o-plus-circle')
                            ->color('success')
                            ->form([
                                Forms\Components\TextInput::make('qtd')
                                    ->label('Quantidade a adicionar')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                            ])
                            ->action(function (Estoque $record, array $data) {
                                \App\Services\EstoqueService::adicionarEstoque($record, $data['qtd']);
                            }),

                        Tables\Actions\Action::make('consumir')
                            ->label('Saída Rápida')
                            ->tooltip('Saída Rápida')
                            ->icon('heroicon-o-minus-circle')
                            ->color('warning')
                            ->form([
                                Forms\Components\TextInput::make('qtd')
                                    ->label('Quantidade a consumir')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                            ])
                            ->action(function (Estoque $record, array $data) {
                                \App\Services\EstoqueService::consumirEstoque($record, $data['qtd']);
                            }),
                    ]
                )
            )
            ->bulkActions(
                \App\Support\Filament\AutonomiaTable::defaultBulkActions()
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO =====
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            TextEntry::make('item')
                                ->label('Produto')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->icon('heroicon-o-cube')
                                ->columnSpan(2),

                            TextEntry::make('status')
                                ->label('Situação')
                                ->badge()
                                ->color(fn(Estoque $record) => $record->cor)
                                ->formatStateUsing(fn(Estoque $record) => $record->isAbaixoDoMinimo() ? 'BAIXO ESTOQUE' : 'NORMAL'),

                            TextEntry::make('unidade')
                                ->label('Unidade')
                                ->badge()
                                ->color('gray'),
                        ]),
                    ]),

                // ===== RESUMO =====
                InfolistSection::make('📊 Indicadores')
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'sm' => 3])->schema([
                            TextEntry::make('quantidade')
                                ->label('Estoque Atual')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->color(fn(Estoque $record) => $record->cor)
                                ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->unidade),

                            TextEntry::make('preco_interno')
                                ->label('Custo Unitário')
                                ->money('BRL')
                                ->color('warning'),

                            TextEntry::make('valor_total')
                                ->label('Valor Total em Estoque')
                                ->money('BRL')
                                ->color('success')
                                ->weight('bold')
                                ->state(fn(Estoque $record) => $record->quantidade * $record->preco_interno),
                        ]),
                    ])
                    ->collapsible(),

                // ===== ABAS =====
                Tabs::make('Detalhes')
                    ->tabs([
                        // ABA 1: GERAL
                        Tabs\Tab::make('📦 Detalhes Gerais')
                            ->schema([
                                InfolistGrid::make(['default' => 1, 'sm' => 3])->schema([
                                    TextEntry::make('minimo_alerta')
                                        ->label('Estoque Mínimo')
                                        ->icon('heroicon-m-bell-alert')
                                        ->suffix(fn($record) => ' ' . $record->unidade),

                                    TextEntry::make('preco_venda')
                                        ->label('Preço Venda (Sugerido)')
                                        ->money('BRL')
                                        ->icon('heroicon-m-currency-dollar'),

                                    TextEntry::make('updated_at')
                                        ->label('Última Movimentação')
                                        ->dateTime('d/m/Y H:i'),
                                ]),
                                TextEntry::make('observacoes')
                                    ->label('Observações')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ]),

                        // ABA 2: HISTÓRICO DE USO (RELACIONAMENTO)
                        Tabs\Tab::make('🛠️ Histórico de Uso em OS')
                            ->badge(fn(Estoque $record) => $record->ordensServico()->count())
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('ordensServico')
                                    ->label('')
                                    ->schema([
                                        InfolistGrid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                                            TextEntry::make('numero_os')
                                                ->label('OS')
                                                ->weight('bold')
                                                ->url(fn($record) => route('filament.admin.resources.ordem-servicos.view', ['record' => $record->id])),

                                            TextEntry::make('pivot.quantidade_utilizada')
                                                ->label('Qtd Utilizada')
                                                ->badge()
                                                ->color('danger')
                                                ->formatStateUsing(fn($state, $record) => $state . ' ' . ($record->pivot->unidade ?? '')),

                                            TextEntry::make('created_at')
                                                ->label('Data')
                                                ->date('d/m/Y'),

                                            TextEntry::make('cliente.nome')
                                                ->label('Cliente')
                                                ->limit(20),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->hidden(fn(Estoque $record) => $record->ordensServico()->count() === 0),

                                TextEntry::make('sem_uso')
                                    ->label('')
                                    ->default('Nenhum uso registrado em Ordens de Serviço.')
                                    ->visible(fn(Estoque $record) => $record->ordensServico()->count() === 0),
                            ]),

                        // ABA 3: HISTÓRICO DE ALTERAÇÕES
                        Tabs\Tab::make('📜 Histórico')
                            ->icon('heroicon-m-clock')
                            ->badge(fn(Estoque $record) => $record->audits()->count())
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('audits')
                                    ->label('')
                                    ->schema([
                                        InfolistGrid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                                            TextEntry::make('user.name')
                                                ->label('Usuário')
                                                ->icon('heroicon-m-user')
                                                ->placeholder('Sistema'),
                                            TextEntry::make('event')
                                                ->label('Ação')
                                                ->badge()
                                                ->formatStateUsing(fn(string $state): string => match ($state) {
                                                    'created' => 'Criação',
                                                    'updated' => 'Edição',
                                                    'deleted' => 'Exclusão',
                                                    default => ucfirst($state),
                                                })
                                                ->color(fn(string $state): string => match ($state) {
                                                    'created' => 'success',
                                                    'updated' => 'warning',
                                                    'deleted' => 'danger',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('created_at')
                                                ->label('Data/Hora')
                                                ->dateTime('d/m/Y H:i:s'),
                                            TextEntry::make('ip_address')
                                                ->label('IP')
                                                ->icon('heroicon-m-globe-alt')
                                                ->copyable(),
                                        ]),
                                    ])
                                    ->grid(1)
                                    ->contained(false),
                                TextEntry::make('sem_historico')
                                    ->label('')
                                    ->default('Nenhuma alteração registrada.')
                                    ->visible(fn(Estoque $record) => $record->audits()->count() === 0),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstoques::route('/'),
            'create' => Pages\CreateEstoque::route('/create'),
            'view' => Pages\ViewEstoque::route('/{record}'),
            'edit' => Pages\EditEstoque::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\ProdutoResource\Widgets\EstoqueVisualWidget::class,
        ];
    }

    public static function canAccess(): bool
    {
        return !auth()->user()?->isFuncionario();
    }
}
