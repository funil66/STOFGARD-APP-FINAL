<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\ChartWidget;

class FluxoCaixaChart extends ChartWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected static ?string $heading = 'Fluxo de Caixa Detalhado';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->subMonths(6)->startOfMonth();
        $endDate = $this->filters['endDate'] ?? now()->endOfMonth();
        $status = $this->filters['status'] ?? 'pago';

        $startDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);

        $entradas = [];
        $saidas = [];
        $labels = [];

        // Loop por mês
        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $monthLabel = $current->locale('pt-br')->format('M Y');
            $start = $current->copy()->startOfMonth();
            $end = $current->copy()->endOfMonth();

            // Query baseada no status escolhido
            if ($status === 'pago') {
                $entradas[] = Financeiro::pago()->entrada()->whereBetween('data_pagamento', [$start, $end])->sum('valor_pago');
                $saidas[] = Financeiro::pago()->saida()->whereBetween('data_pagamento', [$start, $end])->sum('valor_pago');
            } else {
                $entradas[] = Financeiro::pendente()->entrada()->whereBetween('data_vencimento', [$start, $end])->sum('valor');
                $saidas[] = Financeiro::pendente()->saida()->whereBetween('data_vencimento', [$start, $end])->sum('valor');
            }

            $labels[] = ucfirst($monthLabel);
            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $entradas,
                    'borderColor' => '#10b981', // green-500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Saídas',
                    'data' => $saidas,
                    'borderColor' => '#ef4444', // red-500
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // Se o usuário escolher "bar" no principal, aqui podemos usar "line" ou respeitar a escolha.
        // O pedido foi "varied types", então vamos respeitar.
        return $this->filters['chartType'] ?? 'line';
    }
}
