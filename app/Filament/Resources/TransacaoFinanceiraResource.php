<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransacaoFinanceiraResource\Pages; use App\Models\TransacaoFinanceira; use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;

class TransacaoFinanceiraResource extends Resource { protected static ?string $model = TransacaoFinanceira::class;

// Ícone de dinheiro e título correto no menu
protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
protected static ?string $navigationLabel = 'Financeiro';
protected static ?string $modelLabel = 'Transação';
protected static ?string $pluralModelLabel = 'Financeiro';
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Detalhes da Transação')
                ->schema([
                    Forms\Components\TextInput::make('descricao')
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
                                ->required()
                                ->native(false),
                                
                            Forms\Components\Select::make('categoria_id')
                                ->relationship('categoria', 'nome')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('nome')->required(),
                                    Forms\Components\Select::make('tipo')
                                        ->options(['receita'=>'Receita', 'despesa'=>'Despesa'])
                                        ->required(),
                                ]),
                                
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pendente' => 'Pendente',
                                    'pago' => 'Pago',
                                    'atrasado' => 'Atrasado',
                                    'cancelado' => 'Cancelado',
                                ])
                                ->default('pendente')
                                ->required()
                                ->native(false),
                        ]),
                        
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\DatePicker::make('data_vencimento')
                                ->required()
                                ->displayFormat('d/m/Y'),
                                
                            Forms\Components\DatePicker::make('data_pagamento')
                                ->displayFormat('d/m/Y'),
                                
                            Forms\Components\TextInput::make('valor_total')
                                ->label('Valor Total (R$)')
                                ->numeric()
                                ->prefix('R$')
                                ->required(),
                        ]),
                    Forms\Components\Select::make('orcamento_id')
                        ->relationship('orcamento', 'numero')
                        ->searchable()
                        ->label('Vincular Orçamento'),
                        
                    Forms\Components\Textarea::make('observacoes')
                        ->columnSpanFull(),
                ])
        ]);
}
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('data_vencimento')
                ->date('d/m/Y')
                ->sortable()
                ->label('Vencimento'),
                
            Tables\Columns\TextColumn::make('descricao')
                ->searchable()
                ->limit(30)
                ->label('Descrição'),
                
            Tables\Columns\TextColumn::make('categoria.nome')
                ->badge()
                ->label('Categoria'),
                
            Tables\Columns\TextColumn::make('valor_total')
                ->money('BRL')
                ->sortable()
                ->label('Valor'),
                
            Tables\Columns\TextColumn::make('tipo')
                ->badge()
                ->colors([
                    'success' => 'receita',
                    'danger' => 'despesa',
                ]),
                
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->colors([
                    'warning' => 'pendente',
                    'success' => 'pago',
                    'danger' => 'atrasado',
                    'gray' => 'cancelado',
                ]),
        ])
        ->defaultSort('data_vencimento', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pendente' => 'Pendente',
                    'pago' => 'Pago',
                    'atrasado' => 'Atrasado',
                ]),
            Tables\Filters\SelectFilter::make('tipo')
                ->options([
                    'receita' => 'Receita',
                    'despesa' => 'Despesa',
                ]),
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
