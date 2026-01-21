<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaResource\Pages;
use App\Models\Categoria;
use Filament\Forms;
use Filament\Forms\Form;
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

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 10;

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
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('URL amigável (gerado automaticamente)'),
                            ]),

                        Forms\Components\Grid::make(3)
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
                    ->color(fn (string $state): string => match ($state) {
                        'financeiro_receita' => 'success',
                        'financeiro_despesa' => 'danger',
                        'produto' => 'info',
                        'servico' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'financeiro_receita' => 'Receita',
                        'financeiro_despesa' => 'Despesa',
                        'produto' => 'Produto',
                        'servico' => 'Serviço',
                        'cliente' => 'Cliente',
                        'fornecedor' => 'Fornecedor',
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCategorias::route('/'),
            'create' => Pages\CreateCategoria::route('/create'),
            'edit' => Pages\EditCategoria::route('/{record}/edit'),
        ];
    }
}
