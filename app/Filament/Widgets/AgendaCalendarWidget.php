<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AgendaResource;
use App\Models\Agenda;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class AgendaCalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        return Agenda::query()
            ->with(['cliente', 'ordemServico'])
            ->whereBetween('data_hora_inicio', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(fn(Agenda $agenda) => [
                'id' => $agenda->id,
                'title' => $this->formatEventTitle($agenda), // Título simplificado
                'start' => $agenda->data_hora_inicio,
                'end' => $agenda->data_hora_fim,
                'url' => AgendaResource::getUrl('view', ['record' => $agenda]),
                'backgroundColor' => $this->getColorByStatus($agenda->status), // Mantendo Status como cor principal por enquanto
                'borderColor' => $this->getColorByStatus($agenda->status),
                'className' => 'agenda-event-' . $agenda->status, // Classe CSS útil
                'extendedProps' => [
                    'cliente' => $agenda->cliente?->nome,
                    'local' => $agenda->local,
                    'status' => $agenda->status,
                    'tipo' => $agenda->tipo,
                ],
            ])
            ->toArray();
    }

    protected function formatEventTitle(Agenda $agenda): string
    {
        // Ex: "João Silva (Centro)"
        $parts = [];

        // Ícone ou Prefixo curto baseada no Tipo
        $prefix = match ($agenda->tipo) {
            'servico' => '🔧',
            'visita' => '👁️',
            'reuniao' => '🤝',
            default => '📌',
        };

        $cliente = $agenda->cliente ? explode(' ', $agenda->cliente->nome)[0] : 'S/ Cliente';

        // Tentar pegar o bairro do local se possível, ou apenas nome do cliente
        // Assumindo que local é string simples por enquanto

        return "{$prefix} {$cliente}";
    }

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'customCreateButton dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'buttonText' => [
                'today' => 'Hoje',
                'month' => 'Mês',
                'week' => 'Semana',
                'day' => 'Dia',
            ],
            'locale' => 'pt-br',
            'height' => 'auto',
            'contentHeight' => 'auto',
            'editable' => false,
            'selectable' => true,
            'dayMaxEvents' => 5,
            'eventDisplay' => 'block',
            'views' => [
                'dayGridMonth' => [
                    'dayMaxEvents' => 4,
                ],
            ],
            'eventDidMount' => "function(info) {
                // Tooltip nativo simples
                info.el.title = info.event.extendedProps.tipo + ' - ' + info.event.title + ' (' + info.event.extendedProps.status + ')';
            }",
        ];
    }

    protected function getColorByStatus(string $status): string
    {
        return match ($status) {
            'agendado' => '#3b82f6', // Azul
            'em_andamento' => '#f59e0b', // Laranja
            'concluido' => '#10b981', // Verde
            'cancelado' => '#ef4444', // Vermelho
            default => '#6b7280',
        };
    }
}
