<?php

namespace App\Filament\Widgets;

use App\Models\Produto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Widget que exibe produtos com estoque abaixo do mínimo configurado.
 */
class EstoqueBaixoWidget extends BaseWidget
{
    protected static ?string $heading = '⚠️ Alerta de Estoque Baixo';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        return Produto::where('estoque_minimo', '>', 0)
            ->whereColumn('estoque_atual', '<=', 'estoque_minimo')
            ->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Produto::query()
                    ->where('estoque_minimo', '>', 0)
                    ->whereColumn('estoque_atual', '<=', 'estoque_minimo')
                    ->orderByRaw('estoque_atual - estoque_minimo ASC')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Produto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('estoque_atual')
                    ->label('Estoque Atual')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('estoque_minimo')
                    ->label('Mínimo')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('unidade')
                    ->label('Un.'),

                Tables\Columns\TextColumn::make('deficit')
                    ->label('Déficit')
                    ->state(fn (Produto $record) => $record->estoque_minimo - $record->estoque_atual)
                    ->badge()
                    ->color('danger')
                    ->suffix(' un.'),
            ])
            ->paginated(false);
    }
}
