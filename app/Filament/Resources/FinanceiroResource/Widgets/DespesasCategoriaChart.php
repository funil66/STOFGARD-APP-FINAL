<?php

namespace App\Filament\Resources\FinanceiroResource\Widgets;

use App\Models\Financeiro;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DespesasCategoriaChart extends ChartWidget
{
    protected static ?string $heading = 'Despesas por Categoria (Neste Mês)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        // Buscar categorias com maiores despesas no mês atual
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $dados = Financeiro::query()
            ->join('categorias', 'financeiros.categoria_id', '=', 'categorias.id')
            ->where('financeiros.tipo', 'saida')
            ->whereBetween('financeiros.data_vencimento', [$inicioMes, $fimMes])
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
                    'backgroundColor' => $dados->pluck('cor')->map(fn ($cor) => $cor ?? '#6b7280')->toArray(),
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
