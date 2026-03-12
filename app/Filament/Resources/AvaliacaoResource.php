<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvaliacaoResource\Pages;
use App\Models\Avaliacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class AvaliacaoResource extends Resource
{
    protected static ?string $model = Avaliacao::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'Avaliação';

    protected static ?string $pluralModelLabel = 'Avaliações';

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Avaliação do Cliente')
                ->schema([
                    Forms\Components\Select::make('ordem_servico_id')
                        ->label('Ordem de Serviço')
                        ->relationship('ordemServico', 'numero_os')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('cadastro_id')
                        ->label('Cliente')
                        ->relationship('cadastro', 'nome')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('nota')
                        ->label('Nota (0–10)')
                        ->options(array_combine(range(0, 10), range(0, 10)))
                        ->required()
                        ->native(false),

                    Forms\Components\Textarea::make('comentario')
                        ->label('Comentário do Cliente')
                        ->rows(4)
                        ->maxLength(2000),

                    Forms\Components\DateTimePicker::make('respondida_em')
                        ->label('Respondida em')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['ordemServico', 'cadastro']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ordemServico.numero_os')
                    ->label('OS')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('nota')
                    ->label('Nota')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 9 => 'success',
                        $state >= 7 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('classificacao')
                    ->label('NPS')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'promotor' => 'success',
                        'neutro' => 'warning',
                        'detrator' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('respondida_em')
                    ->label('Respondida em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enviada em')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classificacao')
                    ->label('Classificação NPS')
                    ->options([
                        'promotor' => 'Promotor (9-10)',
                        'neutro' => 'Neutro (7-8)',
                        'detrator' => 'Detrator (0-6)',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'promotor' => $query->where('nota', '>=', 9),
                            'neutro' => $query->whereBetween('nota', [7, 8]),
                            'detrator' => $query->where('nota', '<=', 6),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detalhes da Avaliação')
                ->schema([
                    Infolists\Components\TextEntry::make('ordemServico.numero_os')->label('OS'),
                    Infolists\Components\TextEntry::make('cadastro.nome')->label('Cliente'),
                    Infolists\Components\TextEntry::make('nota')
                        ->label('Nota')
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state >= 9 => 'success',
                            $state >= 7 => 'warning',
                            default => 'danger',
                        }),
                    Infolists\Components\TextEntry::make('classificacao')
                        ->label('Classificação NPS')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'promotor' => 'success',
                            'neutro' => 'warning',
                            'detrator' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state) => ucfirst($state)),
                    Infolists\Components\TextEntry::make('comentario')->label('Comentário'),
                    Infolists\Components\TextEntry::make('respondida_em')
                        ->label('Respondida em')
                        ->dateTime('d/m/Y H:i'),
                ])
                ->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvaliacoes::route('/'),
            'view' => Pages\ViewAvaliacao::route('/{record}'),
        ];
    }
}
