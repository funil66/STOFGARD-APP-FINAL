<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TabelaPrecoResource\Pages;
use App\Models\TabelaPreco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TabelaPrecoResource extends Resource
{
    protected static ?string $model = TabelaPreco::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Tabela de Preços';

    protected static ?string $modelLabel = 'Preço';

    protected static ?string $pluralModelLabel = 'Tabela de Preços';

    // Submódulo de Configurações
    protected static ?string $slug = 'configuracoes/tabela-precos';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Serviço')
                    ->schema([
                        Forms\Components\Select::make('tipo_servico')
                            ->label('Tipo de Serviço')
                            ->options(\App\Services\ServiceTypeManager::getOptions())
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('categoria')
                            ->label('Categoria')
                            ->placeholder('Ex: Sofás, Cadeiras, Colchões...')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nome_item')
                            ->label('Nome do Item/Serviço')
                            ->placeholder('Ex: Sofá 3 lugares retrátil e reclinável')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Select::make('unidade_medida')
                            ->label('Unidade de Medida')
                            ->options([
                                'unidade' => 'Unidade',
                                'm2' => 'Metro Quadrado (m²)',
                            ])
                            ->required()
                            ->default('unidade')
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Valores')
                    ->schema([
                        Forms\Components\TextInput::make('preco_vista')
                            ->label('Preço à Vista (PIX)')
                            ->required()
                            ->numeric()
                            ->prefix('R$')
                            ->minValue(0)
                            ->step(0.01)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('preco_prazo')
                            ->label('Preço a Prazo')
                            ->required()
                            ->numeric()
                            ->prefix('R$')
                            ->minValue(0)
                            ->step(0.01)
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Configurações Adicionais')
                    ->schema([
                        Forms\Components\Toggle::make('ativo')
                            ->label('Item Ativo')
                            ->helperText('Desative para ocultar este item dos orçamentos')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->placeholder('Informações adicionais sobre este item/serviço')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => \App\Services\ServiceTypeManager::getColor($state))
                    ->formatStateUsing(fn(string $state): string => \App\Services\ServiceTypeManager::getLabel($state))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('nome_item')
                    ->label('Item/Serviço')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('unidade_medida')
                    ->label('Unidade')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'unidade' => 'UN',
                        'm2' => 'M²',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('preco_vista')
                    ->label('À Vista')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('preco_prazo')
                    ->label('A Prazo')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tipo_servico')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_servico')
                    ->label('Tipo de Serviço')
                    ->options(\App\Services\ServiceTypeManager::getOptions()),

                Tables\Filters\SelectFilter::make('categoria')
                    ->label('Categoria')
                    ->options(function () {
                        return \App\Models\TabelaPreco::query()
                            ->distinct()
                            ->pluck('categoria', 'categoria')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('unidade_medida')
                    ->label('Unidade')
                    ->options([
                        'unidade' => 'Unidade',
                        'm2' => 'Metro Quadrado (m²)',
                    ]),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos'),

                Tables\Filters\TrashedFilter::make()
                    ->label('Excluídos'),
            ])
            ->actions(
                array_merge(
                    \App\Support\Filament\StofgardTable::defaultActions(
                        view: true,
                        edit: true,
                        delete: true,
                        extraActions: [
                            Tables\Actions\Action::make('pdf')
                                ->label('Abrir PDF')
                                ->tooltip('Abrir PDF')
                                ->icon('heroicon-o-document-text')
                                ->color('info')
                                ->url(fn(TabelaPreco $record) => route('tabelapreco.pdf', $record))
                                ->openUrlInNewTab(),

                            Tables\Actions\Action::make('download')
                                ->label('Baixar PDF')
                                ->tooltip('Baixar PDF')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('success')
                                ->url(fn(TabelaPreco $record) => route('tabelapreco.pdf', $record))
                                ->openUrlInNewTab(),

                            Tables\Actions\RestoreAction::make(),
                        ]
                    ),
                )
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informações do Serviço ou Produto')
                    ->schema([
                        InfolistGrid::make(3)
                            ->schema([
                                TextEntry::make('nome_item')
                                    ->label('Nome do Item/Serviço')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                TextEntry::make('tipo_servico')
                                    ->label('Tipo de Serviço')
                                    ->badge()
                                    ->color(fn(string $state): string => \App\Services\ServiceTypeManager::getColor($state))
                                    ->formatStateUsing(fn(string $state): string => \App\Services\ServiceTypeManager::getLabel($state)),

                                TextEntry::make('categoria')
                                    ->label('Categoria'),

                                TextEntry::make('unidade_medida')
                                    ->label('Unidade de Medida')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'unidade' => 'Unidade (UN)',
                                        'm2' => 'Metro Quadrado (m²)',
                                        default => $state,
                                    }),

                                TextEntry::make('preco_vista')
                                    ->label('Preço à Vista (PIX)')
                                    ->money('BRL')
                                    ->color('success')
                                    ->weight('bold'),

                                TextEntry::make('preco_prazo')
                                    ->label('Preço a Prazo')
                                    ->money('BRL')
                                    ->color('warning')
                                    ->weight('bold'),

                                TextEntry::make('ativo')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn($state) => $state ? 'Ativo' : 'Inativo'),

                                TextEntry::make('observacoes')
                                    ->label('Observações')
                                    ->columnSpanFull()
                                    ->placeholder('Nenhuma observação informada.'),
                            ]),
                    ]),
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
            'index' => Pages\ListTabelaPrecos::route('/'),
            'create' => Pages\CreateTabelaPreco::route('/create'),
            'view' => Pages\ViewTabelaPreco::route('/{record}'),
            'edit' => Pages\EditTabelaPreco::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Restrição de acesso: apenas administradores
     */
    public static function canAccess(): bool
    {
        return settings()->isAdmin(auth()->user());
    }
}
