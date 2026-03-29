<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\GlobalAnnouncementResource\Pages;
use App\Models\GlobalAnnouncement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GlobalAnnouncementResource extends Resource
{
    protected static ?string $model = GlobalAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Avisos Globais';
    protected static ?string $modelLabel = 'Aviso Global';
    protected static ?string $pluralModelLabel = 'Avisos Globais';
    protected static ?string $navigationGroup = 'Gestão SaaS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título do Aviso')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('color')
                    ->label('Cor do Banner')
                    ->options([
                        'info' => 'Azul (Informativo)',
                        'success' => 'Verde (Sucesso)',
                        'warning' => 'Amarelo (Atenção)',
                        'danger' => 'Vermelho (Crítico)',
                    ])
                    ->default('info')
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->label('Mensagem')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expira em (Opcional)'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->badge()
                    ->colors([
                        'info' => 'info',
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
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
            'index' => Pages\ManageGlobalAnnouncements::route('/'),
        ];
    }
}
