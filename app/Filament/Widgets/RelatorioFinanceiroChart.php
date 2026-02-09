<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RelatorioFinanceiroChart extends ChartWidget
{
    protected static ?string $heading = 'Fluxo de Caixa (Últimos 6 Meses)';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        // 1. Receitas (Entradas - Pagas)
        $dataEntradas = Trend::query(
            Financeiro::query()->where('tipo', 'entrada')->where('status', 'pago')
        )
            ->between(
                start: now()->subMonths(6),
                end: now(),
            )
            ->perMonth()
            ->sum('valor');

        // 2. Despesas (Saídas - Pagas)
        $dataSaidas = Trend::query(
            Financeiro::query()->where('tipo', 'saida')->where('status', 'pago')
        )
            ->between(
                start: now()->subMonths(6),
                end: now(),
            )
            ->perMonth()
            ->sum('valor');

        return [
            'datasets' => [
                [
                    'label' => 'Receitas',
                    'data' => $dataEntradas->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10b981', // Emerald 500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Despesas',
                    'data' => $dataSaidas->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#ef4444', // Red 500
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $dataEntradas->map(fn (TrendValue $value) => Carbon::parse($value->date)->translatedFormat('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
