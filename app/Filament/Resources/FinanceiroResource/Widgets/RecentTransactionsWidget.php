<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Filament\Resources\FinanceiroResource;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 2; // Display after stats

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Últimas Transações';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FinanceiroResource::getEloquentQuery()
                    ->latest('created_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pago' => 'success',
                        'pendente' => 'warning',
                        'atrasado' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->modalHeading('Editar Transação Rápida')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('descricao')
                                    ->label('Descrição')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required(),

                                Forms\Components\Select::make('categoria_id')
                                    ->relationship('categoria', 'nome')
                                    ->label('Categoria')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nome')->required(),
                                        Forms\Components\Select::make('tipo')
                                            ->options([
                                                'financeiro_receita' => 'Receita',
                                                'financeiro_despesa' => 'Despesa',
                                            ])
                                            ->required(),
                                    ]),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Vencimento')
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pendente' => 'Pendente',
                                        'pago' => 'Pago',
                                        'atrasado' => 'Atrasado',
                                        'cancelado' => 'Cancelado',
                                    ])
                                    ->required()
                                    ->default('pendente'),
                            ]),
                    ]),
            ])
            ->paginated(false)
            ->headerActions([
                Tables\Actions\Action::make('Ver Todas')
                    ->url(FinanceiroResource::getUrl('index'))
                    ->icon('heroicon-m-arrow-right')
                    ->button(),
            ]);
    }
}
