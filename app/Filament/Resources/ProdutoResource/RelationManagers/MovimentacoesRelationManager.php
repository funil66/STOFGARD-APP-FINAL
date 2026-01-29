<?php

namespace App\Filament\Resources\ProdutoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovimentacoesRelationManager extends RelationManager
{
    protected static string $relationship = 'movimentacoes'; // Certifique-se que o Model Produto tem este método

    // ATENÇÃO: Em RelationManager, form() NÃO É STATIC
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'saida' => 'Saída',
                    ])
                    ->required(),
                
                Forms\Components\TextInput::make('quantidade')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\TextInput::make('motivo')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\DateTimePicker::make('data_movimento')
                    ->default(now())
                    ->required(),
            ]);
    }

    // ATENÇÃO: Em RelationManager, table() NÃO É STATIC
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('motivo')
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('quantidade')
                    ->label('Qtd.')
                    ->numeric(),

                Tables\Columns\TextColumn::make('motivo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('data_movimento')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('criado_por')
                    ->label('Resp.')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajuste Manual')
                    // Garante que o ID do produto seja preenchido automaticamente pelo relacionamento
                    ->mutateFormDataUsing(function (array $data) {
                        $data['criado_por'] = auth()->id() ?? 1;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
