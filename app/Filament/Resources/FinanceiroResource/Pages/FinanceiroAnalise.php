<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Filament\Resources\FinanceiroResource\Widgets\DespesasCategoriaChart;
use App\Filament\Resources\FinanceiroResource\Widgets\FluxoCaixaChart;
use App\Filament\Widgets\FinanceiroChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class FinanceiroAnalise extends Page
{
    use HasFiltersForm;

    protected static string $resource = FinanceiroResource::class;

    protected static string $view = 'filament.resources.financeiro-resource.pages.financeiro-analise';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Análise Gráfica';

    protected static ?string $title = 'Análise Gráfica';

    protected static ?string $slug = 'analise';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string|Htmlable
    {
        return '📈 Análise Gráfica';
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuração da Análise')
                    ->description('Personalize a visualização dos dados.')
                    ->schema([
                        Select::make('viewMode')
                            ->label('Visualização')
                            ->options([
                                'consolidado' => 'Receitas vs Despesas',
                                'fluxo' => 'Fluxo de Caixa',
                                'categoria' => 'Por Categoria',
                            ])
                            ->default('consolidado')
                            ->selectablePlaceholder(false)
                            ->live(), // Important for dynamic widget switching

                        Select::make('chartType')
                            ->label('Tipo de Gráfico')
                            ->options([
                                'bar' => 'Barra',
                                'line' => 'Linha',
                            ])
                            ->default('bar')
                            ->visible(fn(Get $get) => $get('viewMode') !== 'categoria'),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pago' => 'Pago (Realizado)',
                                'pendente' => 'Pendente (Previsto)',
                            ])
                            ->default('pago'),

                        DatePicker::make('startDate')
                            ->label('Início')
                            ->default(now()->subMonths(6)->startOfMonth()),

                        DatePicker::make('endDate')
                            ->label('Fim')
                            ->default(now()->endOfMonth()),
                    ])
                    ->columns(5),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        // Access filters directly or via property if available on mount
        // Note: In HasFiltersForm, $this->filters might be available after mount/update.
        // We defer widget selection to the view or use condition inspection.

        $viewMode = $this->filters['viewMode'] ?? 'consolidado';

        $widgets = [];

        if ($viewMode === 'consolidado') {
            $widgets[] = FinanceiroChart::class;
        } elseif ($viewMode === 'fluxo') {
            $widgets[] = FluxoCaixaChart::class;
        } elseif ($viewMode === 'categoria') {
            $widgets[] = DespesasCategoriaChart::class;
        }

        return $widgets;
    }

    public function getVisibleHeaderWidgets(): array
    {
        return $this->getHeaderWidgets();
    }
}
