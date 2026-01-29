<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstoqueResource\Pages;
use App\Models\Estoque;
use App\Models\Produto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EstoqueResource extends Resource
{
    protected static ?string $model = Estoque::class;
    // Ícone corrigido para v3
    protected static ?string $navigationIcon = 'heroicon-o-archive-box'; 
    protected static ?string $navigationGroup = 'Logística';
    protected static ?string $label = 'Movimentação';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('produto_id')
                    ->label('Produto')
                    ->options(Produto::all()->pluck('nome', 'id'))
                    ->searchable()
                    ->required(),
                
                Forms\Components\Select::make('tipo')
                    ->options([
                        'entrada' => 'Entrada (Compra/Devolução)',
                        'saida' => 'Saída (Uso/Perda)',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('quantidade')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                Forms\Components\TextInput::make('motivo')
                    ->maxLength(255),
                    
                Forms\Components\Hidden::make('criado_por')
                    ->default(fn() => Auth::id() ?? 1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('produto.nome')
                    ->label('Produto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('quantidade')->label('Qtd'),
                Tables\Columns\TextColumn::make('motivo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options(['entrada' => 'Entrada', 'saida' => 'Saída']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstoques::route('/'),
            'create' => Pages\CreateEstoque::route('/create'),
            'edit' => Pages\EditEstoque::route('/{record}/edit'),
        ];
    }
}
