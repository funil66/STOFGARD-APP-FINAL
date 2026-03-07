<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocalEstoqueResource\Pages;
use App\Models\LocalEstoque;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LocalEstoqueResource extends Resource
{
    protected static ?string $model = LocalEstoque::class;

    protected static ?string $modelLabel = 'Local de Estoque';
    protected static ?string $pluralModelLabel = 'Locais de Estoque';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Estoque & Produtos';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        // Apenas PRO/ELITE
        return filament()->getTenant() && filament()->getTenant()->temAcessoPremium();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('ativo')
                            ->required()
                            ->default(true),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
            'index' => Pages\ListLocaisEstoque::route('/'),
            'create' => Pages\CreateLocalEstoque::route('/create'),
            'edit' => Pages\EditLocalEstoque::route('/{record}/edit'),
        ];
    }
}
