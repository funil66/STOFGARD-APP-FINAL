<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class FinanceiroChart extends ChartWidget
{
    protected static ?string $heading = 'Receitas vs Despesas (Últimos 6 Meses)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $now = Carbon::now();
        $meses = [];
        $receitas = [];
        $despesas = [];

        if ($activeFilter === 'year' || $activeFilter === 'last_year') {
            $targetYear = $activeFilter === 'year' ? $now->year : $now->subYear()->year;

            // Query unificada: Agrupa por Mês e Tipo
            $data = Financeiro::selectRaw('MONTH(data) as mes, tipo, SUM(valor) as total')
                ->whereYear('data', $targetYear)
                ->groupBy('mes', 'tipo')
                ->get();

            // Preenche os 12 meses
            for ($m = 1; $m <= 12; $m++) {
                $date = Carbon::createFromDate($targetYear, $m, 1);
                $meses[] = $date->locale('pt_BR')->shortMonthName;

                // Filtra da coleção em memória (rápido)
                $receitas[] = $data->where('mes', $m)->where('tipo', 'entrada')->sum('total');
                $despesas[] = $data->where('mes', $m)->where('tipo', 'saida')->sum('total');
            }
        } else {
            // Default: Últimos 6 Meses
            // Também otimizada para uma única query
            $start = $now->copy()->subMonths(5)->startOfMonth();
            $end = $now->copy()->endOfMonth();

            $data = Financeiro::selectRaw('YEAR(data) as ano, MONTH(data) as mes, tipo, SUM(valor) as total')
                ->whereBetween('data', [$start, $end])
                ->groupBy('ano', 'mes', 'tipo')
                ->get();

            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $m = $date->month;
                $y = $date->year;

                $meses[] = $date->locale('pt_BR')->translatedFormat('M/Y');

                $receitas[] = $data->where('ano', $y)->where('mes', $m)->where('tipo', 'entrada')->sum('total');
                $despesas[] = $data->where('ano', $y)->where('mes', $m)->where('tipo', 'saida')->sum('total');
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Receitas',
                    'data' => $receitas,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Despesas',
                    'data' => $despesas,
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                ],
            ],
            'labels' => $meses,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            '6_months' => 'Últimos 6 Meses',
            'year' => 'Este Ano',
            'last_year' => 'Ano Passado',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "R$ " + value.toLocaleString("pt-BR"); }',
                    ],
                ],
            ],
        ];
    }
}
