<?php

namespace App\Filament\Resources\TransacaoFinanceiraResource\Pages;

use App\Filament\Resources\TransacaoFinanceiraResource;
use App\Filament\Widgets\FinanceiroStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransacaoFinanceiras extends ListRecords
{
    protected static string $resource = TransacaoFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Transação')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroStatsWidget::class,
        ];
    }

    public function getDefaultHeaderWidgetsColumnSpan(): int|string|array
    {
        return 'full';
    }
}
