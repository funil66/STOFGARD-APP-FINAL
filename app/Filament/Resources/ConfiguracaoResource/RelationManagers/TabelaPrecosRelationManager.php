<?php

namespace App\Filament\Resources\ConfiguracaoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TabelaPrecosRelationManager extends RelationManager
{
    protected static string $relationship = 'tabela_precos';

    protected static ?string $title = 'Tabela de Preços (Edição Rápida)';

    protected static ?string $icon = 'heroicon-o-currency-dollar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('valor')
                    ->numeric()
                    ->prefix('R$')
                    ->required(),
                Forms\Components\Select::make('tipo_servico')
                    ->options([
                        \App\Enums\ServiceType::Higienizacao->value => 'Higienização',
                        \App\Enums\ServiceType::Impermeabilizacao->value => 'Impermeabilização',
                        \App\Enums\ServiceType::Combo->value => 'Combo',
                        \App\Enums\ServiceType::Outro->value => 'Outro',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('ativo')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->columns([
                Tables\Columns\TextInputColumn::make('nome')
                    ->label('Serviço')
                    ->searchable()
                    ->rules(['required', 'string', 'max:255']),
                Tables\Columns\TextInputColumn::make('valor')
                    ->label('Preço Base')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['required', 'numeric', 'min:0']),
                Tables\Columns\SelectColumn::make('tipo_servico')
                    ->label('Tipo')
                    ->options([
                        \App\Enums\ServiceType::Higienizacao->value => 'Higienização',
                        \App\Enums\ServiceType::Impermeabilizacao->value => 'Impermeabilização',
                        \App\Enums\ServiceType::Combo->value => 'Combo',
                        \App\Enums\ServiceType::Outro->value => 'Outro',
                    ])
                    ->selectablePlaceholder(false)
                    ->rules(['required']),
                Tables\Columns\ToggleColumn::make('ativo')->label('Disponível'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Novo Serviço'),
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
