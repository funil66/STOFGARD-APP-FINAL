<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Orcamento;
use Illuminate\Support\Facades\DB;

class VendasPorServicoChart extends ChartWidget
{
    protected static ?string $heading = 'Vendas por Tipo de Serviço';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Agrupa orçamentos aprovados por tipo_servico
        $data = Orcamento::query()
            ->where('status', 'aprovado')
            ->select('tipo_servico', DB::raw('count(*) as total'))
            ->groupBy('tipo_servico')
            ->get();

        // Mapeia labels e valores
        $labels = $data->pluck('tipo_servico')->map(fn($type) => match ($type) {
            'higienizacao' => 'Higienização',
            'impermeabilizacao' => 'Impermeabilização',
            'combo' => 'Combo',
            'outro' => 'Outros',
            default => ucfirst($type),
        })->toArray();

        $values = $data->pluck('total')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Vendas',
                    'data' => $values,
                    'backgroundColor' => [
                        '#3b82f6', // Blue
                        '#f59e0b', // Amber
                        '#10b981', // Emerald
                        '#8b5cf6', // Violet
                        '#6b7280', // Gray
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
