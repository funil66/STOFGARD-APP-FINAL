<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgendas extends ListRecords
{
    protected static string $resource = AgendaResource::class;

    protected static ?string $title = 'Lista de Agendamentos';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendario')
                ->label('Ver Calendário')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->url(fn() => AgendaResource::getUrl('index')),

            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}
