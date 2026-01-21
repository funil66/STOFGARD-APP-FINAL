<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AgendaResource;
use App\Models\Agenda;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarioWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        return Agenda::query()
            ->where('data_hora_inicio', '>=', $fetchInfo['start'])
            ->where('data_hora_inicio', '<=', $fetchInfo['end'])
            ->get()
            ->map(fn (Agenda $agenda) => [
                'id' => $agenda->id,
                'title' => $agenda->titulo,
                'start' => $agenda->data_hora_inicio->toIso8601String(),
                'end' => $agenda->data_hora_fim->toIso8601String(),
                'url' => AgendaResource::getUrl('view', ['record' => $agenda->id]),
                'backgroundColor' => $agenda->cor,
                'borderColor' => $agenda->cor,
                'textColor' => '#ffffff',
                'allDay' => $agenda->dia_inteiro,
                'extendedProps' => [
                    'tipo' => $agenda->tipo,
                    'status' => $agenda->status,
                    'cliente' => $agenda->cliente?->nome,
                    'local' => $agenda->local,
                ],
            ])
            ->toArray();
    }

    public function getFormSchema(): array
    {
        return AgendaResource::form(
            \Filament\Forms\Form::make()
        )->getSchema();
    }

    public function onEventClick($event): void
    {
        $this->redirect(AgendaResource::getUrl('edit', ['record' => $event['id']]));
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $agenda = Agenda::find($event['id']);

        if ($agenda) {
            $agenda->update([
                'data_hora_inicio' => $event['start'],
                'data_hora_fim' => $event['end'] ?? $event['start'],
            ]);

            return true;
        }

        return false;
    }

    public function config(): array
    {
        return [
            'locale' => 'pt-br',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'buttonText' => [
                'today' => 'Hoje',
                'month' => 'Mês',
                'week' => 'Semana',
                'day' => 'Dia',
            ],
            'firstDay' => 0, // Domingo
            'timeZone' => 'America/Sao_Paulo',
            'editable' => true,
            'droppable' => true,
            'eventStartEditable' => true,
            'eventDurationEditable' => true,
            'dayMaxEvents' => true,
            'navLinks' => true,
            'selectable' => true,
            'selectMirror' => true,
            'nowIndicator' => true,
            'slotMinTime' => '07:00:00',
            'slotMaxTime' => '20:00:00',
            'height' => 'auto',
        ];
    }
}
