<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use App\Filament\Resources\TarefaResource;
use App\Filament\Pages\Calendario;
use App\Filament\Pages\GoogleCalendarSettings;
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
                ->label('Novo Agendamento')
                ->icon('heroicon-o-plus')
                ->color('success'),

            Actions\Action::make('visualizarCalendario')
                ->label('Calendário Visual')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->url(Calendario::getUrl()),

            Actions\Action::make('minhasTarefas')
                ->label('Minhas Tarefas')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->url(url('/admin/agendas/tarefas')),

            Actions\ActionGroup::make([
                Actions\Action::make('googleSync')
                    ->label('Sincronizar Google')
                    ->icon('heroicon-o-arrow-path')
                    ->url(GoogleCalendarSettings::getUrl()),
            ])
                ->label('Mais')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
        ];
    }

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

