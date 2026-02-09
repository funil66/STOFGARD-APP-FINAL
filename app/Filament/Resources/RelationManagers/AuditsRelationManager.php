<?php

namespace App\Filament\Resources\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'Histórico de Alterações';

    protected static ?string $icon = 'heroicon-o-finger-print';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Usuário'),
                Tables\Columns\TextColumn::make('event')
                    ->label('Ação')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i:s'),
                Tables\Columns\TextColumn::make('old_values')->label('Antes')->limit(50),
                Tables\Columns\TextColumn::make('new_values')->label('Depois')->limit(50),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
