<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Models\Financeiro;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;

class DashboardFinanceiro extends Page
{
    use HasFiltersForm;

    protected static string $resource = FinanceiroResource::class;
    protected static string $view = 'filament.resources.financeiro-resource.pages.dashboard';
    protected static ?string $title = '💰 Dashboard Financeiro';
    protected static ?string $navigationLabel = 'Dashboard';

    public function getTitle(): string|Htmlable
    {
        return '💰 Dashboard Financeiro';
    }

    /**
     * Filter form for date range
     */
    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\FinanceiroStatsWidget::class,
            FinanceiroResource\Widgets\FinanceiroChartWidget::class,
        ];
    }

    public function filtersForm(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->default(now()->startOfMonth()),
                Forms\Components\DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->default(now()->endOfMonth()),
            ])
            ->columns(2);
    }

    /**
     * Get filtered data for widgets
     */
    public function getData(): array
    {
        $filters = $this->filters;

        $query = Financeiro::query()
            ->when($filters['data_inicio'], fn($q, $date) => $q->where('data', '>=', $date))
            ->when($filters['data_fim'], fn($q, $date) => $q->where('data', '<=', $date));

        $entradas = (clone $query)->where('tipo', 'entrada')->sum('valor');
        $saidas = (clone $query)->where('tipo', 'saida')->sum('valor');
        $saldo = $entradas - $saidas;

        $pendentes = (clone $query)->where('status', 'pendente')->sum('valor');
        $pagos = (clone $query)->where('status', 'pago')->sum('valor');
        $atrasados = (clone $query)->where('status', 'atrasado')->sum('valor');

        return [
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $saldo,
            'pendentes' => $pendentes,
            'pagos' => $pagos,
            'atrasados' => $atrasados,
        ];
    }
}
