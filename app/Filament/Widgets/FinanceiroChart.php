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
        $meses = [];
        $receitas = [];
        $despesas = [];

        for ($i = 5; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $meses[] = $mes->locale('pt_BR')->translatedFormat('M/Y');

            $receitas[] = Financeiro::where('tipo', 'receita')
                ->whereYear('data', $mes->year)
                ->whereMonth('data', $mes->month)
                ->sum('valor');

            $despesas[] = Financeiro::where('tipo', 'despesa')
                ->whereYear('data', $mes->year)
                ->whereMonth('data', $mes->month)
                ->sum('valor');
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
