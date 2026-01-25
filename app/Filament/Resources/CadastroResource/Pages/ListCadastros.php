<?php

namespace App\Filament\Resources\CadastroResource\Pages;

use App\Filament\Resources\CadastroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCadastros extends ListRecords {
    protected static string $resource = CadastroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
