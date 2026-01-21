<?php

namespace App\Filament\Resources\CadastroResource\Pages;

use App\Filament\Resources\CadastroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListCadastros extends ListRecords
{
    protected static string $resource = CadastroResource::class;

    /**
     * Redirect the index listing to the unified Cadastros view (read-only).
     */
    public function mount(): void
    {
        $url = \App\Filament\Resources\CadastroViewResource::getUrl('index');
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            new \Symfony\Component\HttpFoundation\RedirectResponse($url)
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Cadastro')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('tipo_cadastro')
                ->label('Tipo')
                ->options([
                    'cliente' => 'Cliente',
                    'loja' => 'Loja',
                    'vendedor' => 'Vendedor',
                ]),
            Tables\Filters\SelectFilter::make('estado')
                ->label('Estado')
                ->options([
                    'SP' => 'SP',
                    'MG' => 'MG',
                    'RJ' => 'RJ',
                ]),
            Tables\Filters\TrashedFilter::make(),
        ];
    }


}
