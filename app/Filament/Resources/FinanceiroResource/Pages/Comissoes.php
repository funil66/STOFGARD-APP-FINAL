<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Financeiro;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Central de Comissões
 *
 * Página dedicada para gerenciar comissões de vendedores e parceiros,
 * com ações em lote para baixa e visualização por status.
 */
class Comissoes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FinanceiroResource::class;

    protected static string $view = 'filament.resources.financeiro-resource.pages.comissoes';

    protected static ?string $title = '💼 Central de Comissões';

    protected static ?string $navigationLabel = 'Comissões';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public function getTitle(): string|Htmlable
    {
        return '💼 Central de Comissões';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\ComissoesStatsWidget::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Financeiro::query()
                    ->where('is_comissao', true)
                    ->with(['cadastro', 'ordemServico', 'orcamento'])
                    ->latest('data')
            )
            ->columns([
                Tables\Columns\TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Beneficiário')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->description(fn ($record) => match ($record->cadastro?->tipo) {
                        'vendedor' => '👔 Vendedor',
                        'loja' => '🏪 Loja',
                        'arquiteto' => '📐 Arquiteto',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->descricao),

                Tables\Columns\TextColumn::make('ordemServico.numero_os')
                    ->label('OS')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')->label('Total')),

                Tables\Columns\IconColumn::make('comissao_paga')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('comissao_data_pagamento')
                    ->label('Pago em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendente')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('comissao_paga', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status_comissao')
                    ->label('Status')
                    ->options([
                        'pendente' => '⏳ Pendentes',
                        'paga' => '✅ Pagas',
                    ])
                    ->default('pendente')
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            'pendente' => $query->where('comissao_paga', false),
                            'paga' => $query->where('comissao_paga', true),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('tipo_beneficiario')
                    ->label('Tipo Beneficiário')
                    ->options([
                        'vendedor' => '👔 Vendedores',
                        'loja' => '🏪 Lojas',
                        'arquiteto' => '📐 Arquitetos',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('cadastro', fn ($q) => $q->where('tipo', $data['value']));
                    }),

                Tables\Filters\SelectFilter::make('cadastro_id')
                    ->label('Beneficiário')
                    ->options(fn () => \App\Models\Cadastro::whereIn('tipo', ['vendedor', 'loja', 'arquiteto'])->pluck('nome', 'id'))
                    ->searchable(),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')->label('De'),
                        Forms\Components\DatePicker::make('data_ate')->label('Até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['data_de'], fn ($q, $d) => $q->whereDate('data', '>=', $d))
                            ->when($data['data_ate'], fn ($q, $d) => $q->whereDate('data', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pagar')
                    ->label('Pagar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Financeiro $record) => ! $record->comissao_paga)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Pagamento')
                    ->modalDescription(fn (Financeiro $record) => 'Pagar comissão de R$ '.number_format((float) ($record->valor ?? 0), 2, ',', '.').' para '.($record->cadastro?->nome ?? 'N/A').'?')
                    ->action(function (Financeiro $record) {
                        $record->update([
                            'comissao_paga' => true,
                            'comissao_data_pagamento' => now(),
                            'status' => 'pago',
                            'data_pagamento' => now(),
                            'valor_pago' => $record->valor,
                        ]);

                        Notification::make()
                            ->title('Comissão paga!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('estornar')
                    ->label('Estornar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Financeiro $record) => $record->comissao_paga)
                    ->requiresConfirmation()
                    ->action(function (Financeiro $record) {
                        $record->update([
                            'comissao_paga' => false,
                            'comissao_data_pagamento' => null,
                            'status' => 'pendente',
                            'data_pagamento' => null,
                        ]);

                        Notification::make()
                            ->title('Comissão estornada!')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make()
                    ->url(fn (Financeiro $record) => FinanceiroResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('pagar_selecionadas')
                        ->label('💰 Pagar Selecionadas')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Pagar Comissões em Lote')
                        ->modalDescription(fn (Collection $records) => 'Pagar '.$records->where('comissao_paga', false)->count().' comissões pendentes?')
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->comissao_paga) {
                                    $record->update([
                                        'comissao_paga' => true,
                                        'comissao_data_pagamento' => now(),
                                        'status' => 'pago',
                                        'data_pagamento' => now(),
                                        'valor_pago' => $record->valor,
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("$count comissões pagas!")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('exportar_csv')
                        ->label('📥 Exportar CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $headers = [
                                'Content-type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename=comissoes.csv',
                            ];

                            $callback = function () use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['Data', 'Beneficiário', 'Tipo', 'Descrição', 'Valor', 'Status', 'Pago em']);

                                foreach ($records as $row) {
                                    fputcsv($file, [
                                        $row->data->format('d/m/Y'),
                                        $row->cadastro?->nome ?? '-',
                                        $row->cadastro?->tipo ?? '-',
                                        $row->descricao,
                                        number_format($row->valor, 2, ',', '.'),
                                        $row->comissao_paga ? 'Paga' : 'Pendente',
                                        $row->comissao_data_pagamento?->format('d/m/Y H:i') ?? '-',
                                    ]);
                                }
                                fclose($file);
                            };

                            return response()->streamDownload($callback, 'comissoes.csv', $headers);
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma comissão encontrada')
            ->emptyStateDescription('Comissões serão exibidas aqui quando geradas.')
            ->striped()
            ->poll('30s');
    }
}
