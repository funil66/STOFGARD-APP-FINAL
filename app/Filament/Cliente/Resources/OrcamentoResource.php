<?php

namespace App\Filament\Cliente\Resources;

use App\Filament\Cliente\Resources\OrcamentoResource\Pages;
use App\Models\Orcamento;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Meus Orçamentos';

    protected static ?string $modelLabel = 'Orçamento';

    protected static ?string $pluralModelLabel = 'Orçamentos';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('cadastro_id', auth()->user()->cadastro_id ?? -1);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Vazio, visualização apenas na infolist ou indevida em read-only, usamos o Table action "View"
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Número')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('data_orcamento')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->state(fn(Orcamento $record) => $record->valor_efetivo)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aprovado' => 'success',
                        'recusado' => 'danger',
                        'novo', 'negociacao' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('Baixar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Orcamento $record) => route('orcamento.pdf', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrcamentos::route('/'),
        ];
    }
}
