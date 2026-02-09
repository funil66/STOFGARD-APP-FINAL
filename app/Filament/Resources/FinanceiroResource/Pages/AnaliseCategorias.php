<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Financeiro;
use App\Models\Categoria;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Página de Análise por Categoria
 * 
 * Mostra despesas e receitas agrupadas por categoria,
 * permitindo entender onde o dinheiro está sendo gasto.
 */
class AnaliseCategorias extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FinanceiroResource::class;
    protected static string $view = 'filament.resources.financeiro-resource.pages.analise-categorias';
    protected static ?string $title = '🏷️ Análise por Categoria';
    protected static ?string $navigationLabel = 'Por Categoria';
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public function getTitle(): string|Htmlable
    {
        return '🏷️ Análise por Categoria';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Categoria::query()
                    ->whereIn('tipo', ['financeiro_receita', 'financeiro_despesa'])
                    ->withCount(['financeiros as total_transacoes'])
                    ->withSum(['financeiros as valor_total' => fn($q) => $q->where('status', 'pago')], 'valor')
                    ->withSum(['financeiros as valor_pendente' => fn($q) => $q->where('status', 'pendente')], 'valor')
            )
            ->columns([
                Tables\Columns\ColorColumn::make('cor')
                    ->label('')
                    ->tooltip('Cor da categoria'),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'financeiro_receita' => '💰 Receita',
                        'financeiro_despesa' => '💸 Despesa',
                        default => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'financeiro_receita' => 'success',
                        'financeiro_despesa' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_transacoes')
                    ->label('Qtd.')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('💵 Valor Pago')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn($record) => $record->tipo === 'financeiro_receita' ? 'success' : 'danger')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')),

                Tables\Columns\TextColumn::make('valor_pendente')
                    ->label('⏳ Pendente')
                    ->money('BRL')
                    ->sortable()
                    ->color('warning')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')),

                Tables\Columns\TextColumn::make('percentual')
                    ->label('% do Total')
                    ->getStateUsing(function ($record) {
                        $total = Categoria::query()
                            ->where('tipo', $record->tipo)
                            ->withSum(['financeiros as valor' => fn($q) => $q->where('status', 'pago')], 'valor')
                            ->get()
                            ->sum('valor');

                        return $total > 0 ? round(($record->valor_total / $total) * 100, 1) : 0;
                    })
                    ->suffix('%')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
            ])
            ->defaultSort('valor_total', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo de Categoria')
                    ->options([
                        'financeiro_receita' => '💰 Receitas',
                        'financeiro_despesa' => '💸 Despesas',
                    ]),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')->label('De')->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('data_ate')->label('Até')->default(now()->endOfMonth()),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_transacoes')
                    ->label('Ver Transações')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Categoria $record) => FinanceiroResource::getUrl('index', [
                        'tableFilters[categoria_id][values][]' => $record->id,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->groups([
                Tables\Grouping\Group::make('tipo')
                    ->label('Tipo')
                    ->collapsible(),
            ])
            ->emptyStateHeading('Nenhuma categoria encontrada')
            ->emptyStateDescription('Cadastre categorias financeiras para visualizar a análise.')
            ->striped();
    }
}
