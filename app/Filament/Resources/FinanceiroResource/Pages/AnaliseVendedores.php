<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Financeiro;
use App\Models\Cadastro;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Página de Análise por Vendedor
 * 
 * Mostra o ranking de vendedores por receita gerada,
 * comissões pendentes e pagas, e performance geral.
 */
class AnaliseVendedores extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FinanceiroResource::class;
    protected static string $view = 'filament.resources.financeiro-resource.pages.analise-vendedores';
    protected static ?string $title = '👔 Análise por Vendedor';
    protected static ?string $navigationLabel = 'Por Vendedor';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public function getTitle(): string|Htmlable
    {
        return '👔 Análise por Vendedor';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Cadastro::query()
                    ->where('tipo', 'vendedor')
                    ->withCount(['financeiros as total_transacoes'])
                    ->withSum(['financeiros as receitas_geradas' => fn($q) => $q->where('tipo', 'entrada')->where('status', 'pago')], 'valor')
                    ->withSum(['financeiros as comissoes_pendentes' => fn($q) => $q->where('is_comissao', true)->where('comissao_paga', false)], 'valor')
                    ->withSum(['financeiros as comissoes_pagas' => fn($q) => $q->where('is_comissao', true)->where('comissao_paga', true)], 'valor')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Vendedor')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('loja.nome')
                    ->label('Loja')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Sem loja'),

                Tables\Columns\TextColumn::make('total_transacoes')
                    ->label('Transações')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('receitas_geradas')
                    ->label('💰 Receitas')
                    ->money('BRL')
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')),

                Tables\Columns\TextColumn::make('comissoes_pendentes')
                    ->label('⏳ Comissões Pendentes')
                    ->money('BRL')
                    ->sortable()
                    ->color('warning')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')),

                Tables\Columns\TextColumn::make('comissoes_pagas')
                    ->label('✅ Comissões Pagas')
                    ->money('BRL')
                    ->sortable()
                    ->color('success')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('BRL')),

                Tables\Columns\TextColumn::make('comissao_percentual')
                    ->label('% Comissão')
                    ->suffix('%')
                    ->alignCenter(),
            ])
            ->defaultSort('receitas_geradas', 'desc')
            ->filters([
                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')->label('De')->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('data_ate')->label('Até')->default(now()->endOfMonth()),
                    ])
                    ->query(function ($query, array $data) {
                        // Nota: O filtro afeta os subqueries de withSum
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('loja_id')
                    ->label('Loja')
                    ->relationship('loja', 'nome')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_transacoes')
                    ->label('Ver Transações')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Cadastro $record) => FinanceiroResource::getUrl('index', [
                        'tableFilters[vendedor_direto][value]' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('pagar_comissoes')
                    ->label('Pagar Comissões')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(Cadastro $record) => ($record->comissoes_pendentes ?? 0) > 0)
                    ->url(fn(Cadastro $record) => FinanceiroResource::getUrl('comissoes', [
                        'tableFilters[vendedor_direto][value]' => $record->id,
                    ])),
            ])
            ->emptyStateHeading('Nenhum vendedor encontrado')
            ->emptyStateDescription('Cadastre vendedores para visualizar a análise.')
            ->striped();
    }
}
