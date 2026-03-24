<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Dashboard de KPIs operacionais: taxa de conversão, tempo médio, receita, ticket médio.
 */
class KpiStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '120s';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        try {
            if (!Schema::hasTable((new Orcamento())->getTable())
                || !Schema::hasTable((new OrdemServico())->getTable())
                || !Schema::hasTable((new Financeiro())->getTable())) {
                return [];
            }

            $mesAtual = now()->startOfMonth();
            $mesAnterior = now()->subMonth()->startOfMonth();
            $fimMesAnterior = now()->subMonth()->endOfMonth();

            $orcamentosMes = Orcamento::where('created_at', '>=', $mesAtual)->count();
            $osGeradasMes = OrdemServico::where('created_at', '>=', $mesAtual)
                ->whereNotNull('orcamento_id')
                ->count();
            $taxaConversao = $orcamentosMes > 0
                ? round(($osGeradasMes / $orcamentosMes) * 100, 1)
                : 0;

            $orcMesAnt = Orcamento::whereBetween('created_at', [$mesAnterior, $fimMesAnterior])->count();
            $osGeradasMesAnt = OrdemServico::whereBetween('created_at', [$mesAnterior, $fimMesAnterior])
                ->whereNotNull('orcamento_id')
                ->count();
            $taxaAnt = $orcMesAnt > 0 ? round(($osGeradasMesAnt / $orcMesAnt) * 100, 1) : 0;
            $diffConversao = $taxaConversao - $taxaAnt;

            $tempoMedio = OrdemServico::where('status', 'concluida')
                ->where('data_conclusao', '>=', $mesAtual)
                ->whereNotNull('data_abertura')
                ->whereNotNull('data_conclusao')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (data_conclusao - data_abertura)) / 86400) as media_dias')
                ->value('media_dias');
            $tempoMedio = $tempoMedio ? round($tempoMedio, 1) : 0;

            $receitaMes = Financeiro::entrada()
                ->where('status', 'pago')
                ->where('data_pagamento', '>=', $mesAtual)
                ->sum('valor_pago') ?: 0;

            $receitaMesAnt = Financeiro::entrada()
                ->where('status', 'pago')
                ->whereBetween('data_pagamento', [$mesAnterior, $fimMesAnterior])
                ->sum('valor_pago') ?: 0;

            $diffReceita = $receitaMesAnt > 0
                ? round((($receitaMes - $receitaMesAnt) / $receitaMesAnt) * 100, 1)
                : 0;

            $osConcluidas = OrdemServico::where('status', 'concluida')
                ->where('data_conclusao', '>=', $mesAtual)
                ->count();
            $valorTotal = OrdemServico::where('status', 'concluida')
                ->where('data_conclusao', '>=', $mesAtual)
                ->sum('valor_total');
            $ticketMedio = $osConcluidas > 0 ? $valorTotal / $osConcluidas : 0;

            $osAbertas = OrdemServico::whereNotIn('status', ['concluida', 'cancelada'])->count();
            $osConcluidasMes = OrdemServico::where('status', 'concluida')
                ->where('data_conclusao', '>=', $mesAtual)
                ->count();

            return [
                Stat::make('Taxa de Conversão', $taxaConversao . '%')
                    ->description($diffConversao >= 0 ? "+{$diffConversao}% vs mês anterior" : "{$diffConversao}% vs mês anterior")
                    ->descriptionIcon($diffConversao >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->icon('heroicon-o-arrow-path')
                    ->color($taxaConversao >= 50 ? 'success' : ($taxaConversao >= 25 ? 'warning' : 'danger'))
                    ->chart($this->getConversionTrend()),

                Stat::make('Tempo Médio Execução', $tempoMedio . ' dias')
                    ->description("{$osConcluidasMes} OS concluídas este mês")
                    ->icon('heroicon-o-clock')
                    ->color($tempoMedio <= 3 ? 'success' : ($tempoMedio <= 7 ? 'warning' : 'danger')),

                Stat::make('Receita do Mês', 'R$ ' . number_format($receitaMes, 2, ',', '.'))
                    ->description($diffReceita >= 0 ? "+{$diffReceita}% vs mês anterior" : "{$diffReceita}% vs mês anterior")
                    ->descriptionIcon($diffReceita >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->chart($this->getRevenueTrend()),

                Stat::make('Ticket Médio', 'R$ ' . number_format($ticketMedio, 2, ',', '.'))
                    ->description("{$osAbertas} OS em aberto")
                    ->icon('heroicon-o-receipt-percent')
                    ->color('info'),
            ];
        } catch (Throwable) {
            return [];
        }
    }

    private function getConversionTrend(): array
    {
        try {
            $data = [];
            for ($i = 5; $i >= 0; $i--) {
                $start = now()->subMonths($i)->startOfMonth();
                $end = now()->subMonths($i)->endOfMonth();

                $orc = Orcamento::whereBetween('created_at', [$start, $end])->count();
                $os = OrdemServico::whereBetween('created_at', [$start, $end])
                    ->whereNotNull('orcamento_id')
                    ->count();
                $data[] = $orc > 0 ? round(($os / $orc) * 100) : 0;
            }

            return $data;
        } catch (Throwable) {
            return [0, 0, 0, 0, 0, 0];
        }
    }

    private function getRevenueTrend(): array
    {
        try {
            $data = [];
            for ($i = 5; $i >= 0; $i--) {
                $start = now()->subMonths($i)->startOfMonth();
                $end = now()->subMonths($i)->endOfMonth();

                $data[] = (int) Financeiro::entrada()
                    ->where('status', 'pago')
                    ->whereBetween('data_pagamento', [$start, $end])
                    ->sum('valor_pago');
            }

            return $data;
        } catch (Throwable) {
            return [0, 0, 0, 0, 0, 0];
        }
    }
}
