<?php
namespace App\Filament\Resources;

use App\Filament\Resources\TransacaoFinanceiraResource\Pages;
use App\Models\TransacaoFinanceira;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransacaoFinanceiraResource extends Resource
{
    protected static ?string $model = TransacaoFinanceira::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Transações';
    protected static ?string $modelLabel = 'Transação';
    protected static ?string $pluralModelLabel = 'Transações Financeiras';

    // Submódulo do Financeiro
    protected static ?string $slug = 'financeiros/transacoes';

    // Ocultar da navegação principal
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Transação')
                    ->schema([
                        Forms\Components\TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('tipo')
                                    ->options([
                                        'receita' => 'Receita (+)',
                                        'despesa' => 'Despesa (-)',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('categoria_id')
                                    ->relationship('categoria', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nome')->required(),
                                        Forms\Components\Select::make('tipo')
                                            ->options(['receita' => 'Receita', 'despesa' => 'Despesa'])
                                            ->required(),
                                    ]),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pendente' => 'Pendente',
                                        'pago' => 'Pago',
                                        'atrasado' => 'Atrasado',
                                    ])
                                    ->default('pendente')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Vencimento')
                                    ->required(),

                                Forms\Components\DatePicker::make('data_pagamento')
                                    ->label('Pagamento'),

                                Forms\Components\TextInput::make('valor_total')
                                    ->label('Valor (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('orcamento_id')
                            ->relationship('orcamento', 'numero')
                            ->label('Vincular Orçamento')
                            ->searchable(),
                    ])
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_vencimento')->date('d/m/Y')->sortable()->label('Vencimento'),
                Tables\Columns\TextColumn::make('descricao')->searchable()->label('Descrição'),
                Tables\Columns\TextColumn::make('categoria.nome')->badge()->label('Categoria'),
                Tables\Columns\TextColumn::make('valor_total')->money('BRL')->sortable()->label('Valor'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->colors(['success' => 'receita', 'danger' => 'despesa']),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors(['warning' => 'pendente', 'success' => 'pago', 'danger' => 'atrasado']),
            ])
            ->defaultSort('data_vencimento', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo'),
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\Action::make('share')
                    ->label('')
                    ->tooltip('Compartilhar')
                    ->icon('heroicon-o-share')
                    ->color('success')
                    ->action(function (TransacaoFinanceira $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Link Copiado!')
                            ->body(url("/admin/transacao-financeiras/{$record->id}"))
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListTransacaoFinanceiras::route('/'),
            'create' => Pages\CreateTransacaoFinanceira::route('/create'),
            'edit' => Pages\EditTransacaoFinanceira::route('/{record}/edit'),
        ];
    }
}
