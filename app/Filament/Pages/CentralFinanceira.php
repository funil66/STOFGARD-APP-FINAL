<?php

namespace App\Filament\Pages;

use App\Filament\Resources\FinanceiroResource;
use App\Filament\Resources\FinanceiroResource\Widgets\FinanceiroStatWidget;
use App\Filament\Resources\FinanceiroResource\Widgets\RecentTransactionsWidget;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;

class CentralFinanceira extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.central-financeira';

    protected static ?string $navigationLabel = 'Central Financeira';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'financeiro';

    protected static ?string $title = 'Central Financeira';

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroStatWidget::class,
            // RecentTransactionsWidget moved to blade view
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openCharts')
                ->label('📊 Abrir Gráficos Avançados')
                ->color('primary')
                ->size(ActionSize::Large)
                ->modalHeading('Análise Financeira Avançada')
                ->modalContent(view('filament.pages.partials.financeiro-charts-overlay'))
                ->modalSubmitAction(false)
                ->modalCancelAction(fn ($action) => $action->label('Fechar')),
        ];
    }

    // Mantendo métodos auxiliares para links rápidos se ainda forem usados no blade
    public function getLinks(): array
    {
        return [
            'principal' => [
                [
                    'label' => '📋 Transações',
                    'description' => 'Todas as entradas e saídas',
                    'url' => FinanceiroResource::getUrl('index'),
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'gray',
                ],
                [
                    'label' => '➕ Nova Transação',
                    'description' => 'Cadastrar receita ou despesa',
                    'url' => FinanceiroResource::getUrl('create'),
                    'icon' => 'heroicon-o-plus-circle',
                    'color' => 'success',
                ],
            ],
            // ... (outros grupos podem ser mantidos ou removidos conforme o design final)
        ];
    }
}
