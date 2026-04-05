<?php

namespace App\Filament\Resources\GarantiaResource\Pages;

use App\Filament\Resources\GarantiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGarantias extends ListRecords
{
    protected static string $resource = GarantiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Garantia'),

            Actions\Action::make('configurar')
                ->label('⚙️ Configurar Garantias')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->url('/admin/configuracoes?activeTab=Serviços e Itens')
                ->tooltip('Configure os prazos de garantia por tipo de serviço'),
        ];
    }
}
