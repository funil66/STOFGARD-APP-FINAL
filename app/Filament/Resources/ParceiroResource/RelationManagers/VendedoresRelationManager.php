<?php

namespace App\Filament\Resources\ParceiroResource\RelationManagers;

use App\Models\Parceiro;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms;

class VendedoresRelationManager extends RelationManager
{
    protected static string $relationship = 'vendedores';

    protected static ?string $recordTitleAttribute = 'nome';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->maxLength(255),
                Forms\Components\TextInput::make('telefone')->tel()->mask('(99) 9999-9999'),
                Forms\Components\TextInput::make('celular')->tel()->mask('(99) 99999-9999'),
                Forms\Components\TextInput::make('percentual_comissao')->numeric()->suffix('%')->default(10),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('celular')->label('Celular')->url(fn (Parceiro $record) => $record->link_whatsapp)->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('percentual_comissao')->suffix('%')->numeric(),
            ])
            ->filters([
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
