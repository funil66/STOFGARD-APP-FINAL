<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaFiscalResource\Pages;
use App\Models\NotaFiscal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class NotaFiscalResource extends Resource
{
    protected static ?string $model = NotaFiscal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'Notas Fiscais';

    protected static ?string $modelLabel = 'Nota Fiscal';

    protected static ?string $pluralModelLabel = 'Notas Fiscais';

    // Submódulo do Financeiro
    protected static ?string $slug = 'financeiros/notas-fiscais';

    // Ocultar da navegação principal
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Nota Fiscal')
                    ->schema([
                        Forms\Components\Select::make('cadastro_id')
                            ->label('Cadastro (Cliente, Loja ou Vendedor)')
                            ->options(function () {
                                $clientes = \App\Models\Cliente::all()->mapWithKeys(fn($c) => [
                                    'cliente_' . $c->id => '🧑 Cliente: ' . $c->nome
                                ]);
                                $parceiros = \App\Models\Parceiro::all()->mapWithKeys(fn($p) => [
                                    'parceiro_' . $p->id => ($p->tipo === 'loja' ? '🏪 Loja: ' : '🧑‍💼 Vendedor: ') . $p->nome
                                ]);
                                return $clientes->union($parceiros)->toArray();
                            })
                            ->searchable()
                            ->required(false),

                        Forms\Components\Select::make('ordem_servico_id')
                            ->label('Ordem de Serviço')
                            ->relationship('ordemServico', 'numero_os')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_nf')
                                    ->label('Número NF')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('serie')
                                    ->label('Série')
                                    ->maxLength(255),

                                Forms\Components\DatePicker::make('data_emissao')
                                    ->label('Data de Emissão')
                                    ->required()
                                    ->default(now()),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo')
                                    ->options([
                                        'entrada' => 'Entrada',
                                        'saida' => 'Saída',
                                    ])
                                    ->required()
                                    ->default('saida'),

                                Forms\Components\Select::make('modelo')
                                    ->label('Modelo')
                                    ->options([
                                        'NFe' => 'NFe - Nota Fiscal Eletrônica',
                                        'NFSe' => 'NFSe - Nota Fiscal de Serviço',
                                        'NFCe' => 'NFCe - Nota Fiscal Consumidor',
                                    ])
                                    ->required()
                                    ->default('NFe'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('chave_acesso')
                                    ->label('Chave de Acesso')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\TextInput::make('protocolo_autorizacao')
                                    ->label('Protocolo de Autorização')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Valores')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('valor_produtos')
                                    ->label('Valor Produtos')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(
                                        fn($state, Forms\Set $set, Forms\Get $get) => self::calcularValorTotal($set, $get)
                                    ),

                                Forms\Components\TextInput::make('valor_servicos')
                                    ->label('Valor Serviços')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(
                                        fn($state, Forms\Set $set, Forms\Get $get) => self::calcularValorTotal($set, $get)
                                    ),

                                Forms\Components\TextInput::make('valor_desconto')
                                    ->label('Desconto')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(
                                        fn($state, Forms\Set $set, Forms\Get $get) => self::calcularValorTotal($set, $get)
                                    ),

                                Forms\Components\TextInput::make('valor_total')
                                    ->label('Valor Total')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0)
                                    ->readOnly()
                                    ->dehydrated(),
                            ]),

                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('valor_icms')
                                    ->label('ICMS')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0),

                                Forms\Components\TextInput::make('valor_iss')
                                    ->label('ISS')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0),

                                Forms\Components\TextInput::make('valor_pis')
                                    ->label('PIS')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0),

                                Forms\Components\TextInput::make('valor_cofins')
                                    ->label('COFINS')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0),
                            ]),
                    ]),

                Forms\Components\Section::make('Observações e Status')
                    ->schema([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'rascunho' => 'Rascunho',
                                'emitida' => 'Emitida',
                                'cancelada' => 'Cancelada',
                                'denegada' => 'Denegada',
                            ])
                            ->required()
                            ->default('rascunho'),
                    ]),

                Forms\Components\Section::make('Cancelamento')
                    ->schema([
                        Forms\Components\DateTimePicker::make('data_cancelamento')
                            ->label('Data de Cancelamento'),

                        Forms\Components\Textarea::make('motivo_cancelamento')
                            ->label('Motivo do Cancelamento')
                            ->rows(3),
                    ])
                    ->visible(fn(Forms\Get $get) => $get('status') === 'cancelada'),
            ]);
    }

    protected static function calcularValorTotal(Forms\Set $set, Forms\Get $get): void
    {
        $valorProdutos = (float) ($get('valor_produtos') ?? 0);
        $valorServicos = (float) ($get('valor_servicos') ?? 0);
        $valorDesconto = (float) ($get('valor_desconto') ?? 0);

        $valorTotal = $valorProdutos + $valorServicos - $valorDesconto;

        $set('valor_total', number_format($valorTotal, 2, '.', ''));
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Nota Fiscal')
                    ->schema([
                        Infolists\Components\TextEntry::make('numero_nf')->label('Número')->weight('bold'),
                        Infolists\Components\TextEntry::make('serie')->label('Série'),
                        Infolists\Components\TextEntry::make('data_emissao')->label('Data Emissão')->dateTime('d/m/Y'),
                        Infolists\Components\TextEntry::make('cadastro.nome')->label('Cadastro')->url(fn($record) => $record->cadastro ? url('/admin/clientes/' . $record->cadastro->id) : null),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Valores')
                    ->schema([
                        Infolists\Components\TextEntry::make('valor_produtos')->label('Valor Produtos')->suffix('R$'),
                        Infolists\Components\TextEntry::make('valor_servicos')->label('Valor Serviços')->suffix('R$'),
                        Infolists\Components\TextEntry::make('valor_desconto')->label('Descontos')->suffix('R$'),
                        Infolists\Components\TextEntry::make('valor_total')->label('Total')->suffix('R$')->weight('bold'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Observações e Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                        Infolists\Components\TextEntry::make('observacoes')->label('Observações')->markdown()->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Arquivos')
                    ->schema([
                        Infolists\Components\ImageEntry::make('arquivos')
                            ->label('Arquivos')
                            ->disk('public')
                            ->openUrlInNewTab()
                            ->limit(10)
                            ->height(400)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_nf')
                    ->label('Número NF')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('serie')
                    ->label('Série')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cadastro')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'entrada' => 'info',
                        'saida' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('modelo')
                    ->label('Modelo')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('data_emissao')
                    ->label('Data Emissão')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'rascunho' => 'gray',
                        'emitida' => 'success',
                        'cancelada' => 'danger',
                        'denegada' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'saida' => 'Saída',
                    ]),

                Tables\Filters\SelectFilter::make('modelo')
                    ->label('Modelo')
                    ->options([
                        'NFe' => 'NFe',
                        'NFSe' => 'NFSe',
                        'NFCe' => 'NFCe',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'rascunho' => 'Rascunho',
                        'emitida' => 'Emitida',
                        'cancelada' => 'Cancelada',
                        'denegada' => 'Denegada',
                    ]),

                Tables\Filters\Filter::make('data_emissao')
                    ->form([
                        Forms\Components\DatePicker::make('emitida_de')
                            ->label('Emitida de'),
                        Forms\Components\DatePicker::make('emitida_ate')
                            ->label('Emitida até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['emitida_de'], fn($q, $date) => $q->whereDate('data_emissao', '>=', $date))
                            ->when($data['emitida_ate'], fn($q, $date) => $q->whereDate('data_emissao', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\Action::make('share')
                    ->label('')
                    ->tooltip('Compartilhar')
                    ->icon('heroicon-o-share')
                    ->color('success')
                    ->action(function (NotaFiscal $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Link Copiado!')
                            ->body(url("/admin/nota-fiscals/{$record->id}"))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_emissao', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotaFiscals::route('/'),
            'create' => Pages\CreateNotaFiscal::route('/create'),
            'view' => Pages\ViewNotaFiscal::route('/{record}'),
            'edit' => Pages\EditNotaFiscal::route('/{record}/edit'),
        ];
    }
}
