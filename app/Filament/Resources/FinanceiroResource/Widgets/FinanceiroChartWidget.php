<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class FinanceiroChartWidget extends ChartWidget
{
    protected static ?string $heading = '📈 Receitas vs Despesas (Últimos 6 Meses)';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect();
        $entradas = collect();
        $saidas = collect();

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->locale('pt_BR')->translatedFormat('M/Y');

            $entrada = Financeiro::where('tipo', 'entrada')
                ->whereYear('data', $month->year)
                ->whereMonth('data', $month->month)
                ->sum('valor');

            $saida = Financeiro::where('tipo', 'saida')
                ->whereYear('data', $month->year)
                ->whereMonth('data', $month->month)
                ->sum('valor');

            $months->push($monthName);
            $entradas->push($entrada);
            $saidas->push($saida);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $entradas->toArray(),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                ],
                [
                    'label' => 'Saídas',
                    'data' => $saidas->toArray(),
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#dc2626',
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
