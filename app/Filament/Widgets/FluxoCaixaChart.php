<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class FluxoCaixaChart extends ChartWidget
{
    protected static ?string $heading = 'Fluxo de Caixa Diário (Mês Atual)';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $period = CarbonPeriod::create($start, $end);

        $labels = [];
        $dataSet1 = []; // Saldo Acumulado
        $dataSet2 = []; // Entradas
        $dataSet3 = []; // Saídas

        $saldoAtual = 0;

        // Eager load data
        $transactions = Financeiro::whereBetween('data', [$start, $end])
            ->get()
            ->groupBy(fn($item) => $item->data->format('Y-m-d'));

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');

            $dayTrans = $transactions->get($formattedDate);

            $dayEntrada = $dayTrans ? $dayTrans->where('tipo', 'entrada')->sum('valor') : 0;
            $daySaida = $dayTrans ? $dayTrans->where('tipo', 'saida')->sum('valor') : 0;

            $activeSaldo = $dayEntrada - $daySaida;
            $saldoAtual += $activeSaldo;

            $dataSet1[] = $saldoAtual;
            $dataSet2[] = $dayEntrada;
            $dataSet3[] = $daySaida;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Saldo Acumulado',
                    'data' => $dataSet1,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'type' => 'line',
                ],
                [
                    'label' => 'Entradas',
                    'data' => $dataSet2,
                    'backgroundColor' => '#10b981',
                    'type' => 'bar',
                ],
                [
                    'label' => 'Saídas',
                    'data' => $dataSet3,
                    'backgroundColor' => '#ef4444',
                    'type' => 'bar',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
