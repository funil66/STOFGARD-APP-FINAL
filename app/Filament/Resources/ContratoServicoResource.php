<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContratoServicoResource\Pages;
use App\Models\ContratoServico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContratoServicoResource extends Resource
{
    protected static ?string $model = ContratoServico::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'Contrato Recorrente';

    protected static ?string $pluralModelLabel = 'Contratos Recorrentes';

    protected static ?int $navigationSort = 55;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Contrato')
                ->schema([
                    Forms\Components\TextInput::make('titulo')
                        ->label('Título')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('cadastro_id')
                        ->label('Cliente')
                        ->relationship('cadastro', 'nome')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('tipo_servico')
                        ->label('Tipo de Serviço')
                        ->maxLength(100),

                    Forms\Components\Textarea::make('descricao')
                        ->label('Descrição')
                        ->rows(3),
                ])
                ->columns(2),

            Forms\Components\Section::make('Recorrência e Valores')
                ->schema([
                    Forms\Components\Select::make('frequencia')
                        ->label('Frequência')
                        ->options([
                            'mensal' => 'Mensal',
                            'bimestral' => 'Bimestral',
                            'trimestral' => 'Trimestral',
                            'semestral' => 'Semestral',
                            'anual' => 'Anual',
                        ])
                        ->default('mensal')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('valor')
                        ->label('Valor por Período')
                        ->numeric()
                        ->prefix('R$')
                        ->required(),

                    Forms\Components\DatePicker::make('data_inicio')
                        ->label('Início')
                        ->required()
                        ->default(now()),

                    Forms\Components\DatePicker::make('data_fim')
                        ->label('Término (opcional)')
                        ->nullable(),

                    Forms\Components\DatePicker::make('proximo_agendamento')
                        ->label('Próximo Agendamento')
                        ->helperText('Preenchido automaticamente após cada execução'),

                    Forms\Components\TextInput::make('dia_vencimento')
                        ->label('Dia do Vencimento')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(28)
                        ->default(10),

                    Forms\Components\Select::make('status')
                        ->options([
                            'ativo' => 'Ativo',
                            'pausado' => 'Pausado',
                            'cancelado' => 'Cancelado',
                            'encerrado' => 'Encerrado',
                        ])
                        ->default('ativo')
                        ->required()
                        ->native(false),
                ])
                ->columns(3),

            Forms\Components\Section::make('Automação')
                ->schema([
                    Forms\Components\Toggle::make('gerar_os_automatica')
                        ->label('Gerar OS automaticamente')
                        ->default(true),

                    Forms\Components\Toggle::make('gerar_financeiro_automatico')
                        ->label('Gerar lançamento financeiro automaticamente')
                        ->default(true),

                    Forms\Components\Textarea::make('observacoes')
                        ->label('Observações')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('cadastro')->withCount('ordensServico'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('frequencia')
                    ->label('Frequência')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ativo' => 'success',
                        'pausado' => 'warning',
                        'cancelado' => 'danger',
                        'encerrado' => 'gray',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('proximo_agendamento')
                    ->label('Próximo')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ordens_servico_count')
                    ->label('OS Geradas')
                    ->counts('ordensServico')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ativo' => 'Ativo',
                        'pausado' => 'Pausado',
                        'cancelado' => 'Cancelado',
                        'encerrado' => 'Encerrado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pausar')
                    ->label('Pausar')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ContratoServico $record) => $record->status === 'ativo')
                    ->action(fn (ContratoServico $record) => $record->update(['status' => 'pausado'])),
                Tables\Actions\Action::make('reativar')
                    ->label('Reativar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ContratoServico $record) => $record->status === 'pausado')
                    ->action(fn (ContratoServico $record) => $record->update(['status' => 'ativo'])),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContratosServico::route('/'),
            'create' => Pages\CreateContratoServico::route('/create'),
            'edit' => Pages\EditContratoServico::route('/{record}/edit'),
        ];
    }
}
