<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransacaoFinanceiraResource\Pages;
use App\Filament\Resources\TransacaoFinanceiraResource\RelationManagers;
use App\Models\TransacaoFinanceira;
use App\Filament\Widgets\FinanceiroStats;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransacaoFinanceiraResource extends Resource
{
    protected static ?string $model = TransacaoFinanceira::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tipo')
                    ->required(),
                Forms\Components\TextInput::make('descricao')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('valor')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('data_transacao')
                    ->required(),
                Forms\Components\DatePicker::make('data_vencimento'),
                Forms\Components\DatePicker::make('data_pagamento'),
                Forms\Components\TextInput::make('categoria')
                    ->required(),
                Forms\Components\TextInput::make('categoria_id')
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('metodo_pagamento'),
                Forms\Components\Select::make('ordem_servico_id')
                    ->relationship('ordemServico', 'id'),
                Forms\Components\TextInput::make('cliente_id')
                    ->numeric(),
                Forms\Components\TextInput::make('parceiro_id')
                    ->numeric(),
                Forms\Components\TextInput::make('cadastro_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('parcela_numero')
                    ->numeric(),
                Forms\Components\TextInput::make('parcela_total')
                    ->numeric(),
                Forms\Components\Select::make('transacao_pai_id')
                    ->relationship('transacaoPai', 'id'),
                Forms\Components\Textarea::make('observacoes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('comprovante')
                    ->maxLength(255),
                Forms\Components\Toggle::make('conciliado')
                    ->required(),
                Forms\Components\TextInput::make('criado_por')
                    ->maxLength(10),
                Forms\Components\TextInput::make('atualizado_por')
                    ->maxLength(10),
            ]);
    }

    public static function getHeaderWidgets(): array
    {
        return [
            FinanceiroStats::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->searchable(),

                TextColumn::make('valor_previsto')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('data_vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'pago',
                        'warning' => 'pendente',
                        'danger' => 'atrasado',
                    ]),

                IconColumn::make('tipo')
                    ->options([
                        'heroicon-m-arrow-up-circle' => 'receita',
                        'heroicon-m-arrow-down-circle' => 'despesa',
                    ])
                    ->colors([
                        'success' => 'receita',
                        'danger' => 'despesa',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'atrasado' => 'Atrasado',
                    ]),

                SelectFilter::make('tipo')
                    ->options([
                        'receita' => 'Receita',
                        'despesa' => 'Despesa',
                    ]),
            ])
            ->defaultSort('data_vencimento', 'asc');
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
            'index' => Pages\ListTransacaoFinanceiras::route('/'),
            'create' => Pages\CreateTransacaoFinanceira::route('/create'),
            'edit' => Pages\EditTransacaoFinanceira::route('/{record}/edit'),
        ];
    }
}
