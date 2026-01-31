<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListaDesejoResource\Pages;
use App\Models\ListaDesejo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ListaDesejoResource extends Resource
{
    protected static ?string $model = ListaDesejo::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Lista de Desejos';
    protected static ?string $modelLabel = 'Item Desejado';
    protected static ?string $pluralModelLabel = 'Lista de Desejos';

    // Submódulo do Almoxarifado
    protected static ?string $slug = 'almoxarifado/lista-desejos';

    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Desejado')
                    ->icon('heroicon-o-gift')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome do Item')
                                    ->placeholder('Ex: Nova Extratora')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('prioridade')
                                    ->label('Prioridade')
                                    ->options([
                                        'urgente' => '🔴 Urgente',
                                        'alta' => '🟠 Alta',
                                        'media' => '🟡 Média',
                                        'baixa' => '🟢 Baixa',
                                    ])
                                    ->default('media')
                                    ->required()
                                    ->native(false),
                            ]),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->placeholder('Detalhes do item...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quantidade_desejada')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),

                                Forms\Components\TextInput::make('preco_estimado')
                                    ->label('Preço Estimado (un)')
                                    ->numeric()
                                    ->prefix('R$'),

                                Forms\Components\DatePicker::make('data_prevista_compra')
                                    ->label('Previsão de Compra'),
                            ]),

                        Forms\Components\TextInput::make('link_referencia')
                            ->label('Link de Referência')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('justificativa')
                            ->label('Justificativa')
                            ->placeholder('Por que precisamos deste item?')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pendente' => '⏳ Pendente',
                                'aprovado' => '✅ Aprovado',
                                'comprado' => '🛒 Comprado',
                                'recusado' => '❌ Recusado',
                            ])
                            ->default('pendente')
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Item')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('prioridade')
                    ->label('Prioridade')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        'media' => 'info',
                        'baixa' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'urgente' => '🔴 Urgente',
                        'alta' => '🟠 Alta',
                        'media' => '🟡 Média',
                        'baixa' => '🟢 Baixa',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('quantidade_desejada')
                    ->label('Qtd')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('preco_estimado')
                    ->label('Preço Un.')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_total_estimado')
                    ->label('Total')
                    ->money('BRL')
                    ->state(fn(ListaDesejo $record): float => ($record->quantidade_desejada ?? 1) * ($record->preco_estimado ?? 0))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('data_prevista_compra')
                    ->label('Previsão')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(
                        fn(ListaDesejo $record): string =>
                        $record->data_prevista_compra && $record->data_prevista_compra->isPast() && $record->status === 'pendente'
                        ? 'danger'
                        : 'gray'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'aprovado' => 'success',
                        'comprado' => 'info',
                        'recusado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pendente' => '⏳ Pendente',
                        'aprovado' => '✅ Aprovado',
                        'comprado' => '🛒 Comprado',
                        'recusado' => '❌ Recusado',
                        default => $state,
                    }),
            ])
            ->defaultSort('prioridade')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => '⏳ Pendentes',
                        'aprovado' => '✅ Aprovados',
                        'comprado' => '🛒 Comprados',
                    ]),
                Tables\Filters\SelectFilter::make('prioridade')
                    ->options([
                        'urgente' => '🔴 Urgentes',
                        'alta' => '🟠 Alta',
                        'media' => '🟡 Média',
                        'baixa' => '🟢 Baixa',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->label('')
                    ->tooltip('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(ListaDesejo $record) => $record->status === 'pendente')
                    ->requiresConfirmation()
                    ->action(fn(ListaDesejo $record) => $record->aprovar()),

                Tables\Actions\Action::make('comprar')
                    ->label('')
                    ->tooltip('Marcar como Comprado')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('info')
                    ->visible(fn(ListaDesejo $record) => $record->status === 'aprovado')
                    ->requiresConfirmation()
                    ->action(fn(ListaDesejo $record) => $record->marcarComoComprado()),

                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListaDesejos::route('/'),
            'create' => Pages\CreateListaDesejo::route('/create'),
            'edit' => Pages\EditListaDesejo::route('/{record}/edit'),
        ];
    }
}
