<?php

namespace App\Filament\Resources\FinanceiroResource\Pages;

use App\Filament\Resources\FinanceiroResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFinanceiro extends ViewRecord
{
    protected static string $resource = FinanceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->label('Excluir'),
        ];
    }
}
