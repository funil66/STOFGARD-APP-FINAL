<?php

namespace App\Filament\Resources\GarantiaResource\Pages;

use App\Filament\Resources\GarantiaResource;
use App\Filament\Resources\PerfilGarantiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGarantias extends ListRecords
{
    protected static string $resource = GarantiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('configurar')
                ->label('⚙️ Perfis de Garantia')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->url(PerfilGarantiaResource::getUrl('index'))
                ->tooltip('Cadastre perfis A/B/C e vincule-os aos serviços em Configurações'),
        ];
    }
}
