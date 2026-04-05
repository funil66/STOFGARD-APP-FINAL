<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerfilGarantiaResource\Pages;
use App\Models\PerfilGarantia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PerfilGarantiaResource extends Resource
{
    protected static ?string $model = PerfilGarantia::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Perfil de Garantia';

    protected static ?string $pluralModelLabel = 'Perfis de Garantia';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->label('Nome do Perfil')
                    ->placeholder('Ex: Garantia Padrão (90 dias)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('dias_garantia')
                    ->label('Dias de Garantia')
                    ->required()
                    ->numeric()
                    ->minValue(1),

                Forms\Components\RichEditor::make('termos_legais')
                    ->label('Termos e Observações Legais')
                    ->placeholder('Insira os termos de garantia, exceções, obrigações do cliente, etc.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dias_garantia')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManagePerfilGarantias::route('/'),
        ];
    }
}
