<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContratoRecorrenteResource\Pages;
use App\Models\ContratoRecorrente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContratoRecorrenteResource extends Resource
{
    protected static ?string $model = ContratoRecorrente::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Contratos Recorrentes';
    protected static ?string $modelLabel = 'Contrato Recorrente';
    protected static ?string $navigationGroup = 'Financeiro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')
                    ->relationship('cliente', 'nome')
                    ->searchable()
                    ->required()
                    ->label('Cliente'),

                Forms\Components\TextInput::make('valor')
                    ->required()
                    ->numeric()
                    ->prefix('R$')
                    ->label('Valor'),

                Forms\Components\Select::make('ciclo')
                    ->required()
                    ->options([
                        'MONTHLY' => 'Mensal',
                        'QUARTERLY' => 'Trimestral',
                        'YEARLY' => 'Anual',
                    ])
                    ->label('Ciclo de Cobrança'),

                Forms\Components\DatePicker::make('data_inicio')
                    ->required()
                    ->default(now())
                    ->label('Data de Início'),

                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'ativo' => 'Ativo',
                        'inativo' => 'Inativo',
                    ])
                    ->default('ativo')
                    ->label('Status'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nome')
                    ->searchable()
                    ->sortable()
                    ->label('Cliente'),

                Tables\Columns\TextColumn::make('valor')
                    ->money('BRL')
                    ->sortable()
                    ->label('Valor'),

                Tables\Columns\TextColumn::make('ciclo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'MONTHLY' => 'info',
                        'QUARTERLY' => 'warning',
                        'YEARLY' => 'success',
                        default => 'gray',
                    })
                    ->label('Ciclo'),

                Tables\Columns\TextColumn::make('data_inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Início'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ativo' => 'success',
                        'inativo' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContratoRecorrentes::route('/'),
            'create' => Pages\CreateContratoRecorrente::route('/create'),
            'edit' => Pages\EditContratoRecorrente::route('/{record}/edit'),
        ];
    }
}
