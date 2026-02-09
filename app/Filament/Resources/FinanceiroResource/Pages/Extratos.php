<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Financeiro;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Extratos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FinanceiroResource::class;

    protected static string $view = 'filament.resources.financeiro-resource.pages.extratos';

    protected static ?string $title = 'Extratos Financeiros';

    protected static ?string $slug = 'extratos-financeiros';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public function table(Table $table): Table
    {
        return $table
            ->query(Financeiro::query()->latest('data'))
            ->columns([
                Tables\Columns\TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'saida',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entrada' => 'Entrada',
                        'saida' => 'Saída',
                    }),

                Tables\Columns\TextColumn::make('valor')
                    ->money('BRL')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'pago',
                        'warning' => 'pendente',
                        'danger' => 'atrasado',
                        'gray' => 'cancelado',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('cadastro.nome')
                    ->label('Cliente/Fornecedor')
                    ->limit(20)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('data_inicio')
                            ->label('Data Início'),
                        Forms\Components\DatePicker::make('data_fim')
                            ->label('Data Fim'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['data_inicio'], fn ($q) => $q->whereDate('data', '>=', $data['data_inicio']))
                            ->when($data['data_fim'], fn ($q) => $q->whereDate('data', '<=', $data['data_fim']));
                    }),

                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'entrada' => 'Entradas',
                        'saida' => 'Saídas',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pago' => 'Pago',
                        'pendente' => 'Pendente',
                        'atrasado' => 'Atrasado',
                    ]),

                Tables\Filters\SelectFilter::make('categoria_id')
                    ->relationship('categoria', 'nome')
                    ->label('Categoria')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('comissao_status')
                    ->label('Comissões')
                    ->options([
                        'todas' => 'Todas as Comissões',
                        'paga' => 'Comissões Pagas',
                        'pendente' => 'Comissões Pendentes',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            'todas' => $query->where('is_comissao', true),
                            'paga' => $query->where('is_comissao', true)->where('comissao_paga', true),
                            'pendente' => $query->where('is_comissao', true)->where('comissao_paga', false),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportar_pdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn () => route('extrato.pdf', request()->query('tableFilters', [])))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('data', 'desc');
    }
}
