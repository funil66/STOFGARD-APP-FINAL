<?php

namespace App\Filament\Resources\ProdutoResource\Pages;

use App\Filament\Resources\ProdutoResource;
use Filament\Resources\Pages\ListRecords;

use Filament\Actions;

class ListProdutos extends ListRecords
{
    protected static string $resource = ProdutoResource::class;

    public function mount(): void
    {
        $this->redirect('/admin/almoxarifado/estoques');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
