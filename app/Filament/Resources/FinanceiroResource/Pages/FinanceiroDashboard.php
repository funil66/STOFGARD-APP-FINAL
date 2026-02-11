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
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class FinanceiroDashboard extends Page
{
    use HasFiltersForm;

    protected static string $resource = FinanceiroResource::class;

    protected static string $view = 'filament.resources.financeiro-resource.pages.financeiro-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Dashboard Financeiro';

    protected static ?string $title = 'Dashboard Financeiro';

    protected static ?string $slug = 'dashboard';

    // Ocultar da navegação lateral principal pois será acessado via botão
    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string|Htmlable
    {
        return '📊 Dashboard Financeiro';
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros Globais')
                    ->description('Estes filtros alteram todos os gráficos abaixo.')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Data Início')
                            ->default(now()->subMonths(6)->startOfMonth()),
                        DatePicker::make('endDate')
                            ->label('Data Fim')
                            ->default(now()->endOfMonth()),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pago' => 'Pago (Realizado)',
                                'pendente' => 'Pendente (Previsto)',
                            ])
                            ->default('pago'),
                        Select::make('chartType')
                            ->label('Tipo de Gráfico')
                            ->options([
                                'bar' => 'Barra',
                                'line' => 'Linha',
                                'pie' => 'Pizza',
                            ])
                            ->default('bar'),
                    ])
                    ->columns(4),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroChart::class,
            FluxoCaixaChart::class,
            DespesasCategoriaChart::class,
        ];
    }
}
