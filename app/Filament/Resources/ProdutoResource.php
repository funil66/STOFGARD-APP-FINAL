<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdutoResource\Pages;
use App\Models\Produto;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Produtos';

    protected static ?string $modelLabel = 'Produto';

    protected static ?string $pluralModelLabel = 'Produtos';

    // Submódulo do Almoxarifado
    protected static ?string $slug = 'almoxarifado/produtos';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Principais')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome do Produto')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Precificação e Detalhes')
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('preco_custo')
                                    ->label('Preço de Custo')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0),

                                Forms\Components\TextInput::make('preco_venda')
                                    ->label('Preço de Venda')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->default(0),

                                Forms\Components\TextInput::make('unidade')
                                    ->label('Unidade')
                                    ->placeholder('Ex: Un, Kit, Kg')
                                    ->maxLength(20),
                            ]),
                    ]),

                Forms\Components\Section::make('Imagens')
                    ->collapsible()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('fotos')
                            ->label('Logos / Fotos do Produto')
                            ->collection('produtos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('fotos')
                    ->collection('produtos')
                    ->circular()
                    ->label(''),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('unidade')
                    ->label('Unid.')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('preco_custo')
                    ->label('Custo')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('preco_venda')
                    ->label('Venda')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('margem')
                    ->label('Margem')
                    ->state(function (Produto $record) {
                        if ($record->preco_venda > 0 && $record->preco_custo > 0) {
                            $margem = (($record->preco_venda - $record->preco_custo) / $record->preco_custo) * 100;

                            return number_format($margem, 1) . '%';
                        }

                        return '-';
                    })
                    ->badge()
                    ->color(fn($state) => $state === '-' ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nome')
            ->actions(
                \App\Support\Filament\Autonomia IlimitadaTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        Tables\Actions\Action::make('download')
                            ->label('Baixar PDF')
                            ->tooltip('Baixar PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('success')
                            ->url(fn(Produto $record) => route('produto.pdf', $record))
                            ->openUrlInNewTab(),
                    ]
                )
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ===== CABEÇALHO =====
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            TextEntry::make('nome')
                                ->label('Produto')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->columnSpan(2),
                            TextEntry::make('unidade')
                                ->label('Unidade')
                                ->badge()
                                ->color('gray'),
                            TextEntry::make('margem_calc')
                                ->label('Margem')
                                ->badge()
                                ->state(function ($record) {
                                    if ($record->preco_venda > 0 && $record->preco_custo > 0) {
                                        $margem = (($record->preco_venda - $record->preco_custo) / $record->preco_custo) * 100;
                                        return number_format($margem, 1) . '%';
                                    }
                                    return '-';
                                })
                                ->color(fn($state) => $state === '-' ? 'gray' : 'success'),
                        ]),
                    ]),

                // ===== RESUMO FINANCEIRO =====
                InfolistSection::make('💰 Precificação')
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'sm' => 3])->schema([
                            TextEntry::make('preco_custo')
                                ->label('💵 Custo')
                                ->money('BRL')
                                ->color('warning'),
                            TextEntry::make('preco_venda')
                                ->label('💎 Venda')
                                ->money('BRL')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->color('success'),
                            TextEntry::make('lucro')
                                ->label('📈 Lucro/Un')
                                ->money('BRL')
                                ->weight('bold')
                                ->color('info')
                                ->state(fn($record) => $record->preco_venda - $record->preco_custo),
                        ]),
                    ])
                    ->collapsible(),

                // ===== ABAS =====
                \Filament\Infolists\Components\Tabs::make('Detalhes')
                    ->tabs([
                        // ABA 1: INFORMAÇÕES
                        \Filament\Infolists\Components\Tabs\Tab::make('📋 Informações')
                            ->schema([
                                TextEntry::make('descricao')
                                    ->label('Descrição')
                                    ->columnSpanFull()
                                    ->placeholder('Sem descrição'),
                            ]),

                        // ABA 2: IMAGENS
                        \Filament\Infolists\Components\Tabs\Tab::make('📸 Imagens')
                            ->badge(fn($record) => $record->getMedia('produtos')->count())
                            ->schema([
                                ImageEntry::make('fotos')
                                    ->collection('produtos')
                                    ->label('')
                                    ->limit(10)
                                    ->height(200)
                                    ->columnSpanFull(),
                                TextEntry::make('sem_imagens')
                                    ->label('')
                                    ->default('Nenhuma imagem cadastrada.')
                                    ->visible(fn($record) => $record->getMedia('produtos')->isEmpty()),
                            ]),

                        // ABA 3: HISTÓRICO
                        \Filament\Infolists\Components\Tabs\Tab::make('📜 Histórico')
                            ->icon('heroicon-m-clock')
                            ->badge(fn($record) => $record->audits()->count())
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
                                    ->visible(fn($record) => $record->audits()->count() === 0),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
            'view' => Pages\ViewProduto::route('/{record}'),
        ];
    }
}
