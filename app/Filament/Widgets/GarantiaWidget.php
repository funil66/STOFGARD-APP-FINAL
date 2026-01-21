<?php

namespace App\Filament\Widgets;

use App\Models\Garantia;
use Filament\Widgets\ChartWidget;

class GarantiaWidget extends ChartWidget
{
    protected static ?string $heading = 'Garantias Ativas por Tipo';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $higienizacao = Garantia::ativas()
            ->where('tipo_servico', 'higienizacao')
            ->count();

        $impermeabilizacao = Garantia::ativas()
            ->where('tipo_servico', 'impermeabilizacao')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Garantias Ativas',
                    'data' => [$higienizacao, $impermeabilizacao],
                    'backgroundColor' => [
                        '#3b82f6', // Higienização (90 dias)
                        '#f59e0b', // Impermeabilização (365 dias)
                    ],
                ],
            ],
            'labels' => [
                sprintf('🧹 Higienização (90 dias): %d', $higienizacao),
                sprintf('💧 Impermeabilização (365 dias): %d', $impermeabilizacao),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
