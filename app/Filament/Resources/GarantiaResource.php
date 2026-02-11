<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GarantiaResource\Pages;
use App\Models\Garantia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GarantiaResource extends Resource
{
    protected static ?string $model = Garantia::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Garantias';

    protected static ?string $modelLabel = 'Garantia';

    protected static ?string $pluralModelLabel = 'Garantias';

    // Submódulo de Configurações
    protected static ?string $slug = 'configuracoes/garantias';

    protected static bool $shouldRegisterNavigation = true; // Agora visível

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'ativa')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Garantia')
                    ->schema([
                        Forms\Components\Select::make('ordem_servico_id')
                            ->label('Ordem de Serviço')
                            ->relationship('ordemServico', 'numero_os')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('tipo_servico')
                            ->label('Tipo de Serviço')
                            ->options(\App\Services\ServiceTypeManager::getOptions())
                            ->required()
                            ->disabled(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('data_inicio')
                                    ->label('Data Início')
                                    ->required()
                                    ->disabled()
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                Forms\Components\DatePicker::make('data_fim')
                                    ->label('Data Fim')
                                    ->required()
                                    ->disabled()
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                Forms\Components\TextInput::make('dias_garantia')
                                    ->label('Dias de Garantia')
                                    ->disabled()
                                    ->suffix('dias'),
                            ]),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'ativa' => 'Ativa',
                                'vencida' => 'Vencida',
                                'utilizada' => 'Utilizada',
                                'cancelada' => 'Cancelada',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('usado_em')
                            ->label('Data de Uso')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn($get) => in_array($get('status'), ['utilizada']))
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('motivo_uso')
                            ->label('Motivo do Uso da Garantia')
                            ->rows(3)
                            ->visible(fn($get) => in_array($get('status'), ['utilizada']))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('💡 Garantias são **criadas automaticamente** ao concluir OSs. Configure os prazos por tipo de serviço em **Configurações**.')
            ->columns([
                Tables\Columns\TextColumn::make('ordemServico.numero_os')
                    ->label('OS')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('ordemServico.cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => \App\Services\ServiceTypeManager::getColor($state))
                    ->formatStateUsing(fn(string $state): string => \App\Services\ServiceTypeManager::getLabel($state))
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable()
                    ->visibleFrom('xl'),

                Tables\Columns\TextColumn::make('data_fim')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn($record): string => $record->esta_vencida ? 'danger' : 'success')
                    ->description(function ($record): string {
                        $dias = $record->dias_restantes;
                        if ($dias < 0) {
                            return 'Vencida há ' . abs($dias) . ' dias';
                        }
                        if ($dias === 0) {
                            return 'Vence hoje';
                        }

                        return 'Restam ' . $dias . ' dias';
                    }),

                Tables\Columns\TextColumn::make('dias_garantia')
                    ->label('Prazo')
                    ->suffix(' dias')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ativa' => 'success',
                        'vencida' => 'danger',
                        'utilizada' => 'warning',
                        'cancelada' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'ativa' => '✅ Ativa',
                        'vencida' => '❌ Vencida',
                        'utilizada' => '⚠️ Utilizada',
                        'cancelada' => '🚫 Cancelada',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('usado_em')
                    ->label('Usado em')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_fim', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ativa' => 'Ativa',
                        'vencida' => 'Vencida',
                        'utilizada' => 'Utilizada',
                        'cancelada' => 'Cancelada',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('tipo_servico')
                    ->label('Tipo de Serviço')
                    ->options(\App\Services\ServiceTypeManager::getOptions()),

                Tables\Filters\Filter::make('proximas_vencer')
                    ->label('Próximas a vencer (30 dias)')
                    ->query(fn(Builder $query): Builder => $query->proximasVencer(30)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions(
                \App\Support\Filament\StofgardTable::defaultActions(
                    view: true,
                    edit: true,
                    delete: true,
                    extraActions: [
                        Tables\Actions\Action::make('utilizar')
                            ->label('Usar Garantia')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->color('warning')
                            ->visible(fn($record): bool => $record->status === 'ativa')
                            ->form([
                                Forms\Components\DatePicker::make('usado_em')
                                    ->label('Data de Uso')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now())
                                    ->maxDate(now()),

                                Forms\Components\Textarea::make('motivo_uso')
                                    ->label('Motivo/Problema Relatado')
                                    ->required()
                                    ->rows(3),
                            ])
                            ->action(function ($record, array $data): void {
                                $record->update([
                                    'status' => 'utilizada',
                                    'usado_em' => $data['usado_em'],
                                    'motivo_uso' => $data['motivo_uso'],
                                ]);
                            }),

                        Tables\Actions\Action::make('pdf')
                            ->label('Abrir PDF')
                            ->tooltip('Abrir PDF')
                            ->icon('heroicon-o-document-text')
                            ->color('info')
                            ->url(fn(Garantia $record) => route('garantia.pdf', $record))
                            ->openUrlInNewTab(),

                        Tables\Actions\Action::make('download')
                            ->label('Baixar PDF')
                            ->tooltip('Baixar PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('success')
                            ->url(fn(Garantia $record) => route('garantia.pdf', $record))
                            ->openUrlInNewTab(),
                    ]
                )
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informações da Garantia')
                    ->schema([
                        InfolistGrid::make(2)
                            ->schema([
                                TextEntry::make('numero_garantia')->label('Número da Garantia'),
                                TextEntry::make('status')->label('Status')->badge(),
                                TextEntry::make('data_inicio')->label('Data de Início')->date('d/m/Y'),
                                TextEntry::make('data_fim')->label('Data de Fim')->date('d/m/Y'),
                                TextEntry::make('tipo_servico')->label('Tipo de Serviço'),
                                TextEntry::make('usado_em')->label('Data de Uso')->date('d/m/Y')->visible(fn($record) => $record->usado_em),
                                TextEntry::make('motivo_uso')->label('Motivo de Uso')->columnSpanFull()->visible(fn($record) => $record->motivo_uso),
                            ]),
                    ]),
            ]);
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
            'index' => Pages\ListGarantias::route('/'),
            'view' => Pages\ViewGarantia::route('/{record}'),
            'edit' => Pages\EditGarantia::route('/{record}/edit'),
        ];
    }
}
