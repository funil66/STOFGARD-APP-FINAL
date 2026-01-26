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

    public function getTabs(): array
    {
        return [
            'clientes' => \Filament\Resources\Components\Tab::make('Clientes')
                ->modifyQueryUsing(fn ($query) => $query->where('tipo', 'cliente'))
                ->icon('heroicon-m-user'),
            'parceiros' => \Filament\Resources\Components\Tab::make('Parceiros e Lojas')
                ->modifyQueryUsing(fn ($query) => $query->whereIn('tipo', ['loja', 'vendedor']))
                ->icon('heroicon-m-briefcase'),
            'todos' => \Filament\Resources\Components\Tab::make('Todos'),
        ];
    }
}
