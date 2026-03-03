<?php

namespace App\Filament\Cliente\Resources;

use App\Filament\Cliente\Resources\OrdemServicoResource\Pages;
use App\Models\OrdemServico;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Meus Serviços';

    protected static ?string $modelLabel = 'Serviço';

    protected static ?string $pluralModelLabel = 'Serviços';

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
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_os')
                    ->label('Nº OS')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('descricao_servico')
                    ->label('Descrição')
                    ->limit(40),

                Tables\Columns\TextColumn::make('data_abertura')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'concluida' => 'success',
                        'cancelada' => 'danger',
                        'andamento', 'agendada' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('Baixar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn(OrdemServico $record) => route('os.pdf', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrdemServicos::route('/'),
        ];
    }
}
