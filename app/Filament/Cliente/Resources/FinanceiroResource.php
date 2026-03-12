<?php

namespace App\Filament\Cliente\Resources;

use App\Filament\Cliente\Resources\FinanceiroResource\Pages;
use App\Models\Financeiro;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Financeiro::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Meu Financeiro';

    protected static ?string $modelLabel = 'Fatura';

    protected static ?string $pluralModelLabel = 'Faturas';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('cadastro_id', auth()->user()->cadastro_id ?? -1)
            ->where('tipo', 'entrada') // O cliente só vê suas faturas (entradas da empresa = cobrança ao cliente)
        ;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable(),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(Financeiro $record, $state) => $record->status === 'pendente' && \Carbon\Carbon::parse($state)->isPast() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pago' => 'success',
                        'cancelado' => 'gray',
                        'pendente' => 'warning',
                        'atrasado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('Recibo/Fatura')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn(Financeiro $record) => route('financeiro.pdf', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceiros::route('/'),
        ];
    }
}
