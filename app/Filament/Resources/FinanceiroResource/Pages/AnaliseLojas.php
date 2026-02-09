<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Cadastro;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Página de Análise por Loja
 *
 * Mostra a performance financeira de cada loja,
 * incluindo receitas, despesas e ticket médio.
 */
class AnaliseLojas extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FinanceiroResource::class;

    protected static string $view = 'filament.resources.financeiro-resource.pages.analise-lojas';

    protected static ?string $title = '🏪 Análise por Loja';

    protected static ?string $navigationLabel = 'Por Loja';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public function getTitle(): string|Htmlable
    {
        return '🏪 Análise por Loja';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Cadastro::query()
                    ->where('tipo', 'loja')
                    ->withCount(['financeiros as total_transacoes'])
                    ->withSum(['financeiros as receitas' => fn ($q) => $q->where('tipo', 'entrada')->where('status', 'pago')], 'valor')
                    ->withSum(['financeiros as despesas' => fn ($q) => $q->where('tipo', 'saida')->where('status', 'pago')], 'valor')
                    ->withCount(['vendedores'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Loja')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-storefront')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('vendedores_count')
                    ->label('Vendedores')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_transacoes')
                    ->label('Transações')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('receitas')
                    ->label('💰 Receitas')
                    ->money('BRL')
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')),

                Tables\Columns\TextColumn::make('despesas')
                    ->label('💸 Despesas')
                    ->money('BRL')
                    ->sortable()
                    ->color('danger')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')),

                Tables\Columns\TextColumn::make('saldo')
                    ->label('📊 Saldo')
                    ->getStateUsing(fn ($record) => ($record->receitas ?? 0) - ($record->despesas ?? 0))
                    ->money('BRL')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('ticket_medio')
                    ->label('🎫 Ticket Médio')
                    ->getStateUsing(fn ($record) => $record->total_transacoes > 0
                        ? ($record->receitas ?? 0) / $record->total_transacoes
                        : 0)
                    ->money('BRL')
                    ->color('info'),
            ])
            ->defaultSort('receitas', 'desc')
            ->filters([
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
                    ->url(fn (Cadastro $record) => FinanceiroResource::getUrl('index', [
                        'tableFilters[loja_direto][value]' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('ver_vendedores')
                    ->label('Vendedores')
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Cadastro $record) => FinanceiroResource::getUrl('analise-vendedores', [
                        'tableFilters[loja_id][value]' => $record->id,
                    ])),
            ])
            ->emptyStateHeading('Nenhuma loja encontrada')
            ->emptyStateDescription('Cadastre lojas para visualizar a análise.')
            ->striped();
    }
}
