<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use App\Filament\Widgets\AgendaCalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class CalendarioAgenda extends ListRecords
{
    protected static string $resource = AgendaResource::class;

    protected static ?string $title = 'Agenda';

    // Botão removido daqui, agora está integrado no calendário
    protected function getHeaderActions(): array
    {
        return [];
    }

    // Calendário volta para o topo
    protected function getHeaderWidgets(): array
    {
        return [
            AgendaCalendarWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}

