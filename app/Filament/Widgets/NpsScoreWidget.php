<?php

namespace App\Filament\Widgets;

use App\Models\Avaliacao;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget de NPS (Net Promoter Score) no dashboard.
 * Calcula: (% Promotores − % Detratores) × 100
 */
class NpsScoreWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $total = Avaliacao::respondidas()->count();

        if ($total === 0) {
            return [
                Stat::make('NPS Score', '—')
                    ->description('Nenhuma avaliação ainda')
                    ->icon('heroicon-o-star')
                    ->color('gray'),
                Stat::make('Total Avaliações', '0')
                    ->icon('heroicon-o-clipboard-document-check'),
                Stat::make('Nota Média', '—')
                    ->icon('heroicon-o-chart-bar'),
            ];
        }

        $promotores = Avaliacao::respondidas()->where('nota', '>=', 9)->count();
        $detratores = Avaliacao::respondidas()->where('nota', '<=', 6)->count();
        $media = round(Avaliacao::respondidas()->avg('nota'), 1);

        $nps = round((($promotores - $detratores) / $total) * 100);

        $npsColor = match (true) {
            $nps >= 70 => 'success',
            $nps >= 30 => 'warning',
            default => 'danger',
        };

        $npsZone = match (true) {
            $nps >= 70 => 'Excelência',
            $nps >= 30 => 'Qualidade',
            $nps >= 0 => 'Aperfeiçoamento',
            default => 'Crítico',
        };

        return [
            Stat::make('NPS Score', $nps)
                ->description("Zona de {$npsZone}")
                ->icon('heroicon-o-star')
                ->color($npsColor)
                ->chart($this->getMonthlyNps()),

            Stat::make('Total Avaliações', $total)
                ->description("{$promotores} promotores, {$detratores} detratores")
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info'),

            Stat::make('Nota Média', $media . '/10')
                ->description('Últimos 90 dias')
                ->icon('heroicon-o-chart-bar')
                ->color($media >= 8 ? 'success' : ($media >= 6 ? 'warning' : 'danger')),
        ];
    }

    /**
     * Retorna trend do NPS mensal (últimos 6 meses) para o chart sparkline.
     */
    private function getMonthlyNps(): array
    {
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();

            $total = Avaliacao::respondidas()
                ->whereBetween('respondida_em', [$start, $end])
                ->count();

            if ($total === 0) {
                $data[] = 0;
                continue;
            }

            $promotores = Avaliacao::respondidas()
                ->whereBetween('respondida_em', [$start, $end])
                ->where('nota', '>=', 9)
                ->count();

            $detratores = Avaliacao::respondidas()
                ->whereBetween('respondida_em', [$start, $end])
                ->where('nota', '<=', 6)
                ->count();

            $data[] = round((($promotores - $detratores) / $total) * 100);
        }

        return $data;
    }
}
