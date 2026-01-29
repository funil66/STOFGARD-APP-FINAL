<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Agenda;
use App\Models\OrdemServico;
use Filament\Actions\Action;
use Carbon\Carbon;

class CalendarioWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        $events = [];

        // 1. Puxar Agendamentos Manuais
        $agendas = Agenda::where('data_hora_inicio', '>=', $fetchInfo['start'])
            ->where('data_hora_fim', '<=', $fetchInfo['end'])
            ->get();

        foreach ($agendas as $a) {
            $events[] = [
                'id' => 'agenda-' . $a->id,
                'title' => $a->titulo,
                'start' => $a->data_hora_inicio,
                'end' => $a->data_hora_fim,
                'backgroundColor' => match($a->status) {
                    'concluido' => '#10b981', // Verde
                    'cancelado' => '#ef4444', // Vermelho
                    default => '#3b82f6',     // Azul
                },
                'url' => '/admin/agendas/' . $a->id . '/edit',
            ];
        }

        // 2. Puxar Ordens de Serviço (Previsão)
        $oss = OrdemServico::where('data_inicio', '>=', $fetchInfo['start'])->get();

        foreach ($oss as $os) {
            // Se não tiver data fim, assume 2 horas
            $fim = $os->data_fim ?? Carbon::parse($os->data_inicio)->addHours(2);

            $events[] = [
                'id' => 'os-' . $os->id,
                'title' => "OS {$os->numero_os}",
                'start' => $os->data_inicio,
                'end' => $fim,
                'backgroundColor' => '#f59e0b', // Laranja (OS)
                'borderColor' => '#d97706',
                'url' => '/admin/ordem-servicos/' . $os->id . '/edit',
            ];
        }

        return $events;
    }
    
    // Opcional: Configurar cabeçalho do calendário
    public function config(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'initialView' => 'timeGridWeek',
            'nowIndicator' => true,
        ];
    }
}
