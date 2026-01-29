<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceiroResource\Pages;
use App\Models\Financeiro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Financeiro::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financeiro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cadastro_id')
                    ->relationship('cadastro', 'nome')
                    ->label('Cliente/Parceiro')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('descricao')->required(),
                Forms\Components\TextInput::make('valor')->numeric()->prefix('R$')->required(),
                Forms\Components\DatePicker::make('data_vencimento')->required(),
                Forms\Components\Select::make('status')
                    ->options(['pendente' => 'Pendente', 'pago' => 'Pago', 'atrasado' => 'Atrasado'])
                    ->default('pendente'),
                Forms\Components\Select::make('forma_pagamento')
                    ->options(['pix' => 'PIX', 'cartao' => 'Cartão', 'dinheiro' => 'Dinheiro']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_vencimento')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('cadastro.nome')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('descricao'),
                Tables\Columns\TextColumn::make('valor')->money('BRL'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pago' => 'success',
                        'pendente' => 'warning',
                        'atrasado' => 'danger',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // BOTÃO DE CONFIRMAÇÃO MANUAL
                Tables\Actions\Action::make('confirmar')
                    ->label('Baixar (Recebi)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Financeiro $record) => $record->status === 'pendente')
                    ->requiresConfirmation()
                    ->action(function (Financeiro $record) {
                        $record->update(['status' => 'pago', 'data_pagamento' => now()]);
                        Notification::make()->title('Baixa Realizada!')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceiros::route('/'),
            'create' => Pages\CreateFinanceiro::route('/create'),
            'edit' => Pages\EditFinanceiro::route('/{record}/edit'),
        ];
    }
}
