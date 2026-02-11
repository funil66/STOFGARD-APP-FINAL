<?php

namespace App\Livewire;

use App\Models\Financeiro;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FinanceiroOverlay extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'viewMode' => 'consolidado',
            'chartType' => 'bar',
            'status' => 'pago',
            'startDate' => now()->subMonths(6)->startOfMonth()->format('Y-m-d'),
            'endDate' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('viewMode')
                            ->label('Visualização')
                            ->options([
                                'consolidado' => 'Receitas vs Despesas',
                                'fluxo' => 'Fluxo de Caixa',
                                'categoria' => 'Por Categoria',
                            ])
                            ->default('consolidado')
                            ->selectablePlaceholder(false)
                            ->live(),

                        Forms\Components\Select::make('chartType')
                            ->label('Tipo de Gráfico')
                            ->options(
                                fn(Get $get) => in_array($get('viewMode'), ['categoria', 'forma_pagamento'])
                                ? [
                                    'doughnut' => 'Rosquinha',
                                    'pie' => 'Pizza',
                                    'bar' => 'Barra Horizontal',
                                ]
                                : [
                                    'bar' => 'Barra',
                                    'line' => 'Linha',
                                ]
                            )
                            ->default('bar')
                            ->selectablePlaceholder(false)
                            ->live(),

                        Forms\Components\Select::make('status')
                            ->label('Considerar')
                            ->options([
                                'pago' => 'Realizado (Pago)',
                                'pendente' => 'Previsto (Pendente)',
                            ])
                            ->default('pago')
                            ->selectablePlaceholder(false)
                            ->live(),

                        Forms\Components\Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\DatePicker::make('startDate')
                                    ->label('De')
                                    ->live(),
                                Forms\Components\DatePicker::make('endDate')
                                    ->label('Até')
                                    ->live(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function getChartData(): array
    {
        $formData = $this->form->getState();
        $viewMode = $formData['viewMode'] ?? 'consolidado';
        $startDate = Carbon::parse($formData['startDate']);
        $endDate = Carbon::parse($formData['endDate']);
        $status = $formData['status'];

        if ($viewMode === 'categoria') {
            return $this->getCategoriaData($startDate, $endDate, $status);
        }

        // Common logic for Consolidated and Fluxo (Time Series)
        $meses = [];
        $dataset1 = []; // Receitas / Entradas
        $dataset2 = []; // Despesas / Saídas

        $transactions = Financeiro::query()
            ->whereBetween($status === 'pago' ? 'data_pagamento' : 'data_vencimento', [$startDate, $endDate])
            ->where('status', $status === 'pago' ? 'pago' : 'pendente')
            ->get();

        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $m = (int) $current->format('m');
            $y = (int) $current->format('Y');
            $meses[] = $current->locale('pt_BR')->translatedFormat('M/Y');

            $fnFilter = function ($t) use ($m, $y, $status) {
                $dateRef = $status === 'pago' ? $t->data_pagamento : $t->data_vencimento;
                if (!$dateRef)
                    return false;
                return (int) Carbon::parse($dateRef)->format('m') === $m
                    && (int) Carbon::parse($dateRef)->format('Y') === $y;
            };

            $val = fn($t) => $status === 'pago' ? $t->valor_pago : $t->valor;

            $dataset1[] = $transactions->filter(fn($t) => $fnFilter($t) && $t->tipo === 'entrada')->sum($val);
            $dataset2[] = $transactions->filter(fn($t) => $fnFilter($t) && $t->tipo === 'saida')->sum($val);

            $current->addMonth();
        }

        return [
            'labels' => $meses,
            'datasets' => [
                [
                    'label' => $viewMode === 'consolidado' ? 'Receitas' : 'Entradas',
                    'data' => $dataset1,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                    'fill' => $viewMode === 'fluxo',
                ],
                [
                    'label' => $viewMode === 'consolidado' ? 'Despesas' : 'Saídas',
                    'data' => $dataset2,
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                    'borderWidth' => 2,
                    'fill' => $viewMode === 'fluxo',
                ],
            ],
        ];
    }

    protected function getCategoriaData(Carbon $start, Carbon $end, string $status): array
    {
        $dados = Financeiro::query()
            ->join('categorias', 'financeiros.categoria_id', '=', 'categorias.id')
            ->where('financeiros.tipo', 'saida')
            ->whereBetween($status === 'pago' ? 'financeiros.data_pagamento' : 'financeiros.data_vencimento', [$start, $end])
            ->where('financeiros.status', $status === 'pago' ? 'pago' : 'pendente')
            ->select('categorias.nome', 'categorias.cor', DB::raw('SUM(financeiros.valor) as total'))
            ->groupBy('categorias.id', 'categorias.nome', 'categorias.cor')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $dados->pluck('nome')->toArray(),
            'datasets' => [
                [
                    'label' => 'Despesas por Categoria',
                    'data' => $dados->pluck('total')->toArray(),
                    'backgroundColor' => $dados->pluck('cor')->map(fn($c) => $c ?? '#6b7280')->toArray(),
                ],
            ],
        ];
    }

    public function getChartType(): string
    {
        $viewMode = $this->data['viewMode'] ?? 'consolidado';

        // Evolved category is always stacked bar
        if ($viewMode === 'evolucao_categoria') {
            return 'bar';
        }

        // For others, respect the user selection, but ensure validity
        $type = $this->data['chartType'] ?? 'bar';

        // Fix potential mismatch when switching modes (e.g. valid 'line' in consolidated -> switch to category -> 'line' is invalid)
        if (in_array($viewMode, ['categoria', 'forma_pagamento'])) {
            return in_array($type, ['pie', 'doughnut', 'bar']) ? $type : 'doughnut';
        }

        return in_array($type, ['bar', 'line']) ? $type : 'bar';
    }

    public function getOptions(): array
    {
        $viewMode = $this->data['viewMode'] ?? 'consolidado';
        $isStacked = $viewMode === 'evolucao_categoria';

        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'stacked' => $isStacked,
                    'display' => !in_array($this->getChartType(), ['pie', 'doughnut']),
                ],
                'y' => [
                    'stacked' => $isStacked,
                    'display' => !in_array($this->getChartType(), ['pie', 'doughnut']),
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "R$ " + value.toLocaleString("pt-BR"); }',
                    ],
                ],
            ],
            'indexAxis' => ($viewMode !== 'consolidado' && $viewMode !== 'fluxo' && $viewMode !== 'evolucao_categoria' && $this->getChartType() === 'bar') ? 'y' : 'x',
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.financeiro-overlay');
    }
}
