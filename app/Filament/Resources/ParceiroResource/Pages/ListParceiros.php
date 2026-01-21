<?php

namespace App\Filament\Resources\ParceiroResource\Pages;

use App\Filament\Resources\ParceiroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParceiros extends ListRecords
{
    protected static string $resource = ParceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Parceiro')
                ->icon('heroicon-o-plus'),
        ];
    }
}
