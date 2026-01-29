<?php

namespace App\Filament\Resources\ProdutoResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovimentacoesRelationManager extends RelationManager
{
    protected static string $relationship = 'movimentacoes';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('quantidade'),
                TextColumn::make('motivo'),
                TextColumn::make('data_movimento')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ajuste Manual'),
            ]);
    }
}
