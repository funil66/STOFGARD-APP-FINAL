<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GarantiaResource\Pages;
use App\Models\Garantia;
use Filament\Forms;
use Filament\Forms\Form;
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

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 6;

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
                            ->options(\App\Enums\ServiceType::class)
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
                    ->limit(30),

                Tables\Columns\TextColumn::make('tipo_servico')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => \App\Enums\ServiceType::tryFrom($state)?->getColor() ?? 'gray')
                    ->formatStateUsing(fn(string $state): string => \App\Enums\ServiceType::tryFrom($state)?->getLabel() ?? $state),

                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),

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
                    ->options(\App\Enums\ServiceType::class),

                Tables\Filters\Filter::make('proximas_vencer')
                    ->label('Próximas a vencer (30 dias)')
                    ->query(fn(Builder $query): Builder => $query->proximasVencer(30)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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

                Tables\Actions\ViewAction::make()->label('')->tooltip('Visualizar'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\Action::make('share')
                    ->label('')
                    ->tooltip('Compartilhar')
                    ->icon('heroicon-o-share')
                    ->color('success')
                    ->action(function (Garantia $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Link Copiado!')
                            ->body(url("/admin/garantias/{$record->id}"))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'create' => Pages\CreateGarantia::route('/create'),
            'edit' => Pages\EditGarantia::route('/{record}/edit'),
        ];
    }
}
