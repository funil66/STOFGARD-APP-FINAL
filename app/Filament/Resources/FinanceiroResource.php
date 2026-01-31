<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceiroResource\Pages;
use App\Models\Financeiro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Financeiro::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Financeiro';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Transação')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'entrada' => '💰 Entrada (Receita)',
                                'saida' => '📤 Saída (Despesa)',
                            ])
                            ->required()
                            ->default('entrada')
                            ->live(),

                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cliente/Fornecedor')
                            ->options(function () {
                                $clientes = \App\Models\Cliente::all()->mapWithKeys(fn($c) => ["cliente_{$c->id}" => "👤 {$c->nome} (Cliente)"]);
                                $parceiros = \App\Models\Parceiro::all()->mapWithKeys(fn($p) => ["parceiro_{$p->id}" => "🏢 {$p->nome_fantasia} (Parceiro)"]);
                                return $clientes->union($parceiros);
                            })
                            ->searchable()
                            ->preload() // Opcional, dependendo da quantidade
                            ->required(),

                        Forms\Components\TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor (R$)')
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
                                        Forms\Components\ColorPicker::make('cor'),
                                    ]),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pendente' => 'Pendente',
                                        'pago' => 'Pago',
                                        'atrasado' => 'Atrasado',
                                        'cancelado' => 'Cancelado',
                                    ])
                                    ->default('pendente')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('data')
                                    ->label('Data da Transação')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Vencimento')
                                    ->required(),

                                Forms\Components\DatePicker::make('data_pagamento')
                                    ->label('Data do Pagamento')
                                    ->nullable(),
                            ]),

                        Forms\Components\Select::make('forma_pagamento')
                            ->label('Forma de Pagamento')
                            ->options([
                                'pix' => 'PIX',
                                'dinheiro' => 'Dinheiro',
                                'cartao_credito' => 'Cartão de Crédito',
                                'cartao_debito' => 'Cartão de Débito',
                                'boleto' => 'Boleto',
                                'transferencia' => 'Transferência',
                            ]),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Comprovantes e Anexos')
                    ->description('Envie comprovantes, notas fiscais e documentos relacionados (Máx: 20MB)')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('arquivos')
                            ->label('Arquivos')
                            ->collection('arquivos')
                            ->multiple()
                            ->disk('public')
                            ->maxSize(20480)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'entrada' => '💰 Entrada',
                        'saida' => '📤 Saída',
                    }),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cliente/Fornecedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('categoria.nome')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn($record) => $record->categoria?->tipo === 'financeiro_receita' ? 'success' : ($record->categoria?->tipo === 'financeiro_despesa' ? 'danger' : 'gray'))
                    ->icon(fn($record) => $record->categoria?->icone),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pago' => 'success',
                        'pendente' => 'warning',
                        'atrasado' => 'danger',
                        'cancelado' => 'gray',
                    }),
            ])
            ->defaultSort('data', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'entrada' => 'Entradas',
                        'saida' => 'Saídas',
                    ]),

                Tables\Filters\SelectFilter::make('status'),

                Tables\Filters\Filter::make('data_range')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')
                            ->label('Data de'),
                        Forms\Components\DatePicker::make('data_ate')
                            ->label('Data até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['data_de'], fn($q, $date) => $q->whereDate('data', '>=', $date))
                            ->when($data['data_ate'], fn($q, $date) => $q->whereDate('data', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('baixar')
                    ->label('Baixar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Financeiro $record) => $record->status === 'pendente')
                    ->requiresConfirmation()
                    ->action(function (Financeiro $record) {
                        $record->update([
                            'status' => 'pago',
                            'data_pagamento' => now()
                        ]);
                        Notification::make()
                            ->title('✅ Pagamento Confirmado!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\Action::make('share')
                    ->label('')
                    ->tooltip('Compartilhar')
                    ->icon('heroicon-o-share')
                    ->color('success')
                    ->action(function (Financeiro $record) {
                        Notification::make()
                            ->title('Link Copiado!')
                            ->body(url("/admin/financeiros/{$record->id}"))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('baixar_selecionados')
                        ->label('Baixar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pendente') {
                                    $record->update([
                                        'status' => 'pago',
                                        'data_pagamento' => now()
                                    ]);
                                }
                            });
                            Notification::make()
                                ->title('Pagamentos confirmados em lote!')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceiros::route('/'),
            'create' => Pages\CreateFinanceiro::route('/create'),
            'edit' => Pages\EditFinanceiro::route('/{record}/edit'),
            'dashboard' => Pages\DashboardFinanceiro::route('/dashboard'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\FinanceiroStatsWidget::class,
            FinanceiroResource\Widgets\FinanceiroChartWidget::class,
        ];
    }
}
