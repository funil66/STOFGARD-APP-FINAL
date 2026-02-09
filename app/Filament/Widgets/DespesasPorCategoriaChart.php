<?php

namespace App\Filament\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DespesasPorCategoriaChart extends ChartWidget
{
    protected static ?string $heading = 'Despesas por Categoria (Este Mês)';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'half';

    protected function getData(): array
    {
        $data = Financeiro::query()
            ->select('categoria_id', DB::raw('sum(valor) as total'))
            ->where('tipo', 'saida') // Only expenses
            ->whereMonth('data', now()->month)
            ->whereYear('data', now()->year)
            ->with('categoria')
            ->groupBy('categoria_id')
            ->orderByDesc('total')
            ->limit(10) // Top 10 categories
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Despesas',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => [
                        '#f87171',
                        '#fb923c',
                        '#facc15',
                        '#4ade80',
                        '#2dd4bf',
                        '#38bdf8',
                        '#818cf8',
                        '#a78bfa',
                        '#f472b6',
                        '#fb7185'
                    ],
                ],
            ],
            'labels' => $data->map(fn($item) => $item->categoria?->nome ?? 'Sem Categoria')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
