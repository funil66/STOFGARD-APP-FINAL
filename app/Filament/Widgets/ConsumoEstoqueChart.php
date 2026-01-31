<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Estoque;
use Illuminate\Support\Facades\DB;

class ConsumoEstoqueChart extends ChartWidget
{
    protected static ?string $heading = 'Histórico de Estoque (Últimos 30 dias)';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Para simplificar, vamos mostrar dados mockados
        // Em produção você criaria uma tabela de histórico

        $labels = [];
        $datasets = [];

        // Gerar últimos 30 dias
        for ($i = 29; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('d/m');
        }

        // Dados para cada produto
        foreach (Estoque::all() as $produto) {
            $data = [];
            $estoqueAtual = $produto->quantidade;

            // Simular variação (em produção viria do histórico real)
            for ($i = 29; $i >= 0; $i--) {
                // Adicionar variação aleatória para demonstração
                $variacao = rand(-5, 5);
                $data[] = max(0, $estoqueAtual + ($variacao * $i / 10));
            }

            $datasets[] = [
                'label' => $produto->item,
                'data' => $data,
                'borderColor' => $produto->cor === 'danger' ? 'rgb(239, 68, 68)' :
                    ($produto->cor === 'warning' ? 'rgb(245, 158, 11)' : 'rgb(34, 197, 94)'),
                'backgroundColor' => $produto->cor === 'danger' ? 'rgba(239, 68, 68, 0.1)' :
                    ($produto->cor === 'warning' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(34, 197, 94, 0.1)'),
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Volume (Litros)',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
