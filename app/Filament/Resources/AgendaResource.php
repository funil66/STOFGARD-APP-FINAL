<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendaResource\Pages;
use App\Models\Agenda;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AgendaResource extends Resource
{
    protected static ?string $model = Agenda::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Operacional';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titulo')->required()->columnSpanFull(),
                Forms\Components\Select::make('cadastro_id')
                    ->relationship('cadastro', 'nome')
                    ->searchable(),
                Forms\Components\DateTimePicker::make('data_hora_inicio')->required(),
                Forms\Components\DateTimePicker::make('data_hora_fim')->required(),
                Forms\Components\Select::make('status')
                    ->options(['agendado' => 'Agendado', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'])
                    ->default('agendado'),
                Forms\Components\TextInput::make('local'),
                Forms\Components\Hidden::make('criado_por')->default(fn() => Auth::id() ?? 1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_hora_inicio')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('titulo')->searchable(),
                Tables\Columns\TextColumn::make('cadastro.nome')->label('Cliente'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'concluido' => 'success',
                        'cancelado' => 'danger',
                        default => 'primary',
                    }),
            ])
            ->defaultSort('data_hora_inicio', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgendas::route('/'),
            'create' => Pages\CreateAgenda::route('/create'),
            'edit' => Pages\EditAgenda::route('/{record}/edit'),
        ];
    }
}
