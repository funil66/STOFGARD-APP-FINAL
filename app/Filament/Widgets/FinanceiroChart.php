<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FinanceiroChart extends ChartWidget
{
    protected static ?string $heading = 'Receitas vs Despesas (Últimos 6 Meses)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        try {
            if (!Schema::hasTable((new Financeiro())->getTable())) {
                return [
                    'datasets' => [
                        ['label' => 'Receitas', 'data' => []],
                        ['label' => 'Despesas', 'data' => []],
                    ],
                    'labels' => [],
                ];
            }

            $activeFilter = $this->filter;
            $now = Carbon::now();
            $meses = [];
            $receitas = [];
            $despesas = [];

            if ($activeFilter === 'year' || $activeFilter === 'last_year') {
                $targetYear = $activeFilter === 'year' ? $now->year : $now->subYear()->year;

                $start = Carbon::createFromDate($targetYear, 1, 1)->startOfYear();
                $end = Carbon::createFromDate($targetYear, 12, 31)->endOfYear();

                $transactions = Financeiro::whereBetween('data', [$start, $end])
                    ->get();

                for ($m = 1; $m <= 12; $m++) {
                    $date = Carbon::createFromDate($targetYear, $m, 1);
                    $meses[] = $date->locale('pt_BR')->shortMonthName;

                    $receitas[] = $transactions->filter(fn($t) => (int) Carbon::parse($t->data)->format('m') === $m && $t->tipo === 'entrada')->sum('valor');
                    $despesas[] = $transactions->filter(fn($t) => (int) Carbon::parse($t->data)->format('m') === $m && $t->tipo === 'saida')->sum('valor');
                }
            } else {
                $start = $now->copy()->subMonths(5)->startOfMonth();
                $end = $now->copy()->endOfMonth();

                $transactions = Financeiro::whereBetween('data', [$start, $end])
                    ->get();

                for ($i = 5; $i >= 0; $i--) {
                    $date = Carbon::now()->subMonths($i);
                    $m = (int) $date->format('m');
                    $y = (int) $date->format('Y');

                    $meses[] = $date->locale('pt_BR')->translatedFormat('M/Y');

                    $receitas[] = $transactions->filter(fn($t) => (int) Carbon::parse($t->data)->format('m') === $m && (int) Carbon::parse($t->data)->format('Y') === $y && $t->tipo === 'entrada')->sum('valor');
                    $despesas[] = $transactions->filter(fn($t) => (int) Carbon::parse($t->data)->format('m') === $m && (int) Carbon::parse($t->data)->format('Y') === $y && $t->tipo === 'saida')->sum('valor');
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
        } catch (Throwable) {
            return [
                'datasets' => [
                    ['label' => 'Receitas', 'data' => []],
                    ['label' => 'Despesas', 'data' => []],
                ],
                'labels' => [],
            ];
        }
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
