<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DespesasCategoriaChart extends ChartWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected static ?string $heading = 'Despesas por Categoria';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->subMonths(6)->startOfMonth();
        $endDate = $this->filters['endDate'] ?? now()->endOfMonth();
        $status = $this->filters['status'] ?? 'pago';

        // Buscar categorias com maiores despesas no período
        $dados = Financeiro::query()
            ->join('categorias', 'financeiros.categoria_id', '=', 'categorias.id')
            ->where('financeiros.tipo', 'saida')
            ->whereBetween($status === 'pago' ? 'financeiros.data_pagamento' : 'financeiros.data_vencimento', [$startDate, $endDate])
            ->where('financeiros.status', $status === 'pago' ? 'pago' : 'pendente')
            ->select('categorias.nome', 'categorias.cor', DB::raw('SUM(financeiros.valor) as total'))
            ->groupBy('categorias.id', 'categorias.nome', 'categorias.cor')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Despesas',
                    'data' => $dados->pluck('total')->toArray(),
                    'backgroundColor' => $dados->pluck('cor')->map(fn($cor) => $cor ?? '#6b7280')->toArray(),
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $dados->pluck('nome')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
