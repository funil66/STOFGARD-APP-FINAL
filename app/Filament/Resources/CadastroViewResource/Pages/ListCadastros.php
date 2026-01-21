<?php

namespace App\Filament\Resources\CadastroViewResource\Pages;

use App\Filament\Resources\CadastroViewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCadastros extends ListRecords
{
    protected static string $resource = CadastroViewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('novo_cadastro')
                ->label('Novo Cadastro')
                ->icon('heroicon-o-plus')
                ->url(\App\Filament\Resources\CadastroResource::getUrl('create')),
        ];
    }
}
