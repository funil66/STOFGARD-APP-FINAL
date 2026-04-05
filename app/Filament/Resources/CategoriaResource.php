<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaResource\Pages;
use App\Models\Categoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Categorias';

    protected static ?string $modelLabel = 'Categoria';

    protected static ?string $pluralModelLabel = 'Categorias';

    // Slug direto para acesso
    protected static ?string $slug = 'categorias';

    protected static ?string $navigationGroup = 'Configurações';

    // Acessado via Financeiro > Categorias ou Configurações hub
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Categoria')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'financeiro_receita' => '💰 Financeiro - Receita',
                                'financeiro_despesa' => '💸 Financeiro - Despesa',
                                'produto' => '📦 Produto',
                                'servico' => '🧹 Serviço',
                                'cliente' => '👥 Cliente',
                                'fornecedor' => '🏭 Fornecedor',
                                'estoque_unidade' => '📏 Estoque - Unidade',
                                'cadastro_tipo' => '👤 Cadastro - Tipo',
                                'servico_tipo' => '🛠️ Serviço - Tipo',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('URL amigável (gerado automaticamente)'),
                            ]),

                        Forms\Components\Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('icone')
                                    ->label('Ícone')
                                    ->helperText('Emoji ou classe de ícone')
                                    ->placeholder('🏷️')
                                    ->maxLength(255),

                                Forms\Components\ColorPicker::make('cor')
                                    ->label('Cor')
                                    ->helperText('Para gráficos e visualizações'),

                                Forms\Components\TextInput::make('ordem')
                                    ->label('Ordem')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Ordem de exibição'),
                            ]),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icone')
                    ->label('')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'financeiro_receita' => 'success',
                        'financeiro_despesa' => 'danger',
                        'produto' => 'info',
                        'servico' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'financeiro_receita' => 'Receita',
                        'financeiro_despesa' => 'Despesa',
                        'produto' => 'Produto',
                        'servico' => 'Serviço',
                        'cliente' => 'Cliente',
                        'fornecedor' => 'Fornecedor',
                        'estoque_unidade' => 'Unidade (Estoque)',
                        'cadastro_tipo' => 'Tipo de Cadastro',
                        'servico_tipo' => 'Tipo de Serviço',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\ColorColumn::make('cor')
                    ->label('Cor'),

                Tables\Columns\TextColumn::make('ordem')
                    ->label('Ordem')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('ordem')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'financeiro_receita' => 'Receita',
                        'financeiro_despesa' => 'Despesa',
                        'produto' => 'Produto',
                        'servico' => 'Serviço',
                    ]),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Ativo')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas ativos')
                    ->falseLabel('Apenas inativos'),
            ])
            ->actions(
                \App\Support\Filament\StofgardTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        Tables\Actions\Action::make('pdf')
                            ->label('Abrir PDF')
                            ->icon('heroicon-o-document-text')
                            ->color('info')
                            ->url(fn(Categoria $record) => route('categoria.pdf', $record))
                            ->openUrlInNewTab(),

                        Tables\Actions\Action::make('download')
                            ->label('Baixar PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('success')
                            ->url(fn(Categoria $record) => route('categoria.pdf', $record))
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
                InfolistSection::make()
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'sm' => 3])->schema([
                            TextEntry::make('nome')
                                ->label('Nome da Categoria')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('bold')
                                ->columnSpan(2),
                            TextEntry::make('ativo')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'danger')
                                ->formatStateUsing(fn($state) => $state ? '✅ Ativo' : '❌ Inativo'),
                        ]),
                    ]),

                InfolistSection::make('📋 Detalhes')
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'sm' => 3])->schema([
                            TextEntry::make('tipo')
                                ->label('Tipo')
                                ->badge()
                                ->color('info')
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'financeiro_receita' => '💰 Receita',
                                    'financeiro_despesa' => '💸 Despesa',
                                    'produto' => '📦 Produto',
                                    default => $state,
                                }),
                            TextEntry::make('slug')
                                ->label('Identificador (Slug)')
                                ->copyable(),
                            TextEntry::make('ordem')
                                ->label('Ordem de Exibição')
                                ->badge()
                                ->color('gray'),
                        ]),
                        InfolistGrid::make(['default' => 1, 'sm' => 2])->schema([
                            TextEntry::make('icone')
                                ->label('Ícone')
                                ->formatStateUsing(fn($state) => $state ?? '📌')
                                ->size(TextEntry\TextEntrySize::Large),
                            TextEntry::make('cor')
                                ->label('Cor')
                                ->color(fn($record) => $record->cor ?? 'gray')
                                ->badge(),
                        ]),
                        TextEntry::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->placeholder('Sem descrição'),
                    ]),

                InfolistSection::make('📊 Estatísticas')
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'sm' => 2])->schema([
                            TextEntry::make('created_at')
                                ->label('Criado em')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('updated_at')
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
            'index' => Pages\ListCategorias::route('/'),
            'create' => Pages\CreateCategoria::route('/create'),
            'edit' => Pages\EditCategoria::route('/{record}/edit'),
            'view' => Pages\ViewCategoria::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        return !auth()->user()?->isFuncionario();
    }
}
