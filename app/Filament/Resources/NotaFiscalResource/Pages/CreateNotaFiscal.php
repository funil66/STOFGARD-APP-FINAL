<?php

namespace App\Filament\Resources\NotaFiscalResource\Pages;

use App\Filament\Resources\NotaFiscalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaFiscal extends CreateRecord
{
    protected static string $resource = NotaFiscalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
