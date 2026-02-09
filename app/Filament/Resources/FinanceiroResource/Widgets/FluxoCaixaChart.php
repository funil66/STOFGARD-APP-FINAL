<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\ChartWidget;

class FluxoCaixaChart extends ChartWidget
{
    protected static ?string $heading = 'Fluxo de Caixa (Últimos 6 Meses)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = $this->getMonthlyData();

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $data['entradas'],
                    'borderColor' => '#10b981', // green-500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Saídas',
                    'data' => $data['saidas'],
                    'borderColor' => '#ef4444', // red-500
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getMonthlyData(): array
    {
        $entradas = [];
        $saidas = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $monthLabel = $date->locale('pt-br')->format('M Y');

            $entradas[] = Financeiro::pago()
                ->entrada()
                ->whereBetween('data_pagamento', [$monthStart, $monthEnd])
                ->sum('valor_pago');

            $saidas[] = Financeiro::pago()
                ->saida()
                ->whereBetween('data_pagamento', [$monthStart, $monthEnd])
                ->sum('valor_pago');

            $labels[] = ucfirst($monthLabel);
        }

        return [
            'entradas' => $entradas,
            'saidas' => $saidas,
            'labels' => $labels,
        ];
    }
}
