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

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Novo Agendamento')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->button()
                ->size('lg'),
        ];
    }

    // Movido calendário para footer widgets para não sobrepor o botão
    protected function getFooterWidgets(): array
    {
        return [
            AgendaCalendarWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}

