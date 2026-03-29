<?php

namespace App\Filament\Resources\PdfGeracaoResource\Pages;

use App\Filament\Resources\PdfGeracaoResource;
use Filament\Resources\Pages\ListRecords;

class ListPdfGeracoes extends ListRecords
{
    protected static string $resource = PdfGeracaoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
