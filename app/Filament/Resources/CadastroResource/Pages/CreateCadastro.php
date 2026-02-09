<?php

namespace App\Filament\Resources\CadastroResource\Pages;

use App\Filament\Resources\CadastroResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCadastro extends CreateRecord
{
    protected static string $resource = CadastroResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
