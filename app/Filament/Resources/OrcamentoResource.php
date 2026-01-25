<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrcamentoResource\Pages;
use App\Models\Orcamento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\TabelaPreco;
use App\Models\Configuracao;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Orçamentos';
    protected static ?string $modelLabel = 'Orçamento';
    protected static ?string $pluralModelLabel = 'Orçamentos';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // GRUPO 1: NEGOCIAÇÃO
                Forms\Components\Section::make('Negociação')
                    ->schema([
                        Forms\Components\Toggle::make('aplicar_desconto_pix')
                            ->label('Aplicar 10% no Pix')
                            ->default(true)
                            ->live(),
                        Forms\Components\Toggle::make('repassar_taxas')
                            ->label('Repassar Taxas')
                            ->default(true)
                            ->live(),
                    ])->columns(2),
            // GRUPO 2: DADOS
            Forms\Components\Section::make('Dados')
                ->schema([
                    Forms\Components\Select::make('cliente_id')
                        ->relationship('cliente', 'nome')
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('parceiro_id')
                        ->relationship('parceiro', 'nome')
                        ->searchable()
                        ->placeholder('Opcional'),
                    Forms\Components\DatePicker::make('data_orcamento')
                        ->default(now())
                        ->required(),
                    Forms\Components\DatePicker::make('data_validade')
                        ->default(now()->addDays(7))
                        ->required(),
                ])->columns(2),
            // GRUPO 3: ITENS (Ocultei a lógica complexa temporariamente para isolar erro)
            Forms\Components\Section::make('Itens')
                ->schema([
                    Forms\Components\Repeater::make('itens')
                        ->relationship('itens')
                        ->schema([
                            Forms\Components\Select::make('tipo_servico')
                                ->options(['higienizacao'=>'Higienização', 'impermeabilizacao'=>'Impermeabilização'])
                                ->default('higienizacao')
                                ->required(),
                            Forms\Components\TextInput::make('item')
                                ->required(),
                            Forms\Components\TextInput::make('quantidade')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                }),
                            Forms\Components\TextInput::make('valor_unitario')
                                ->numeric()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                }),
                        ])
                        ->columns(4)
                ]),
            // GRUPO 4: FECHAMENTO
            Forms\Components\Section::make('Total')
                ->schema([
                    Forms\Components\TextInput::make('valor_total')
                        ->numeric()
                        ->readOnly(),
                    Forms\Components\Select::make('status')
                        ->options(['pendente'=>'Pendente', 'aprovado'=>'Aprovado'])
                        ->default('pendente')
                        ->required(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('numero_orcamento')->label('Nº')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cliente.nome')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('parceiro.nome')
                    ->label('Parceiro')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valor_total')->money('BRL')->label('Total'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aprovado' => 'success',
                        'rejeitado', 'cancelado' => 'danger',
                        'pendente' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->date('d/m/Y')->label('Data'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'aprovado' => 'Aprovado',
                        'rejeitado' => 'Rejeitado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Orcamento $record) => route('orcamento.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Excluir Selecionados')
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\RelationManagers\AuditsRelationManager::class,
        ];
    }

    // Helper para recalcular total a partir dos itens do repeater
    public static function updateTotals(Get $get, Set $set): void
    {
        $itens = $get('itens') ?? [];
        $total = 0;
        foreach ($itens as $item) {
            $qtd = floatval($item['quantidade'] ?? 0);
            $val = floatval($item['valor_unitario'] ?? 0);
            $total += $qtd * $val;
        }
        $set('valor_total', $total);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrcamentos::route('/'),
            'create' => Pages\CreateOrcamento::route('/create'),
            'view' => Pages\ViewOrcamento::route('/{record}'),
            'edit' => Pages\EditOrcamento::route('/{record}/edit'),
        ];
    }
}
