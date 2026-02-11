<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Financeiro;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class FinanceiroStatWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Totais Gerais (Considerando Pagos)
        // Totais Gerais (Considerando Pagos)
        $entradasPagas = Financeiro::where('status', 'pago')->where('tipo', 'entrada')->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(valor_pago, valor)'));
        $saidasPagas = Financeiro::where('status', 'pago')->where('tipo', 'saida')->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(valor_pago, valor)'));
        $saldo = $entradasPagas - $saidasPagas;

        // Mês Atual (Range para filtros futuros)
        // Por padrão: mês atual
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $receitasTotal = Financeiro::where('tipo', 'entrada')->sum('valor'); // "Todas as receitas" conforme solicitado
        $despesasTotal = Financeiro::where('tipo', 'saida')->sum('valor'); // "Todas as despesas" conforme solicitado

        // Previsto (Lançado e não pago)
        $receitasPendentes = Financeiro::where('status', '!=', 'pago')->where('tipo', 'entrada')->sum('valor');
        $despesasPendentes = Financeiro::where('status', '!=', 'pago')->where('tipo', 'saida')->sum('valor');
        $previsto = $receitasPendentes - $despesasPendentes;

        return [
            Stat::make('Saldo em Conta', Number::currency($saldo, 'BRL'))
                ->description('Receitas Pagas (' . Number::currency($entradasPagas, 'BRL') . ') - Despesas Pagas (' . Number::currency($saidasPagas, 'BRL') . ')')
                ->descriptionIcon($saldo >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($saldo >= 0 ? 'success' : 'danger')
                ->chart([$saldo - 100, $saldo - 50, $saldo, $saldo + 50, $saldo + 100])
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:shadow-lg transition-all duration-300',
                    'wire:click' => "\$dispatch('open-modal', { id: 'saldo-details' })", // Exemplo para futuro overlay
                ]),

            Stat::make('Receitas Totais', Number::currency($receitasTotal, 'BRL'))
                ->description('Total histórico lançado')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('success')
                ->chart([$receitasTotal * 0.8, $receitasTotal * 0.9, $receitasTotal])
                ->url(FinanceiroResource::getUrl('index', ['tableFilters' => ['tipo' => ['value' => 'entrada']]])),

            Stat::make('Despesas Totais', Number::currency($despesasTotal, 'BRL'))
                ->description('Total histórico lançado')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('danger')
                ->chart([$despesasTotal * 0.8, $despesasTotal * 0.9, $despesasTotal])
                ->url(FinanceiroResource::getUrl('index', ['tableFilters' => ['tipo' => ['value' => 'saida']]])),

            Stat::make('Previsto', Number::currency($previsto, 'BRL'))
                ->description('Saldo de lançamentos pendentes')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([$previsto * 0.9, $previsto, $previsto * 1.1])
                ->url(FinanceiroResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pendente']]])),
        ];
    }
}
