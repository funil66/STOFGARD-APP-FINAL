<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AgendaResource;
use App\Models\Agenda;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class AgendaCalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = Agenda::class;

    public function fetchEvents(array $info): array
    {
        return Agenda::query()
            ->where('data_hora_inicio', '>=', $info['start'])
            ->where('data_hora_fim', '<=', $info['end'])
            ->get()
            ->map(function (Agenda $agenda) {
                // Definir emoji baseado no tipo
                $emoji = match ($agenda->tipo) {
                    'visita' => '🚗',
                    'servico' => '🧼',
                    'follow_up' => '📞',
                    'reuniao' => '👥',
                    'outro' => '📌',
                    default => '📅',
                };

                // Definir emoji baseado no status
                $statusEmoji = match ($agenda->status) {
                    'confirmado' => '✅',
                    'em_andamento' => '🔄',
                    'concluido' => '✔️',
                    'cancelado' => '❌',
                    default => '',
                };

                $title = $emoji.' '.$agenda->titulo;
                if ($statusEmoji) {
                    $title .= ' '.$statusEmoji;
                }

                return [
                    'id' => $agenda->id,
                    'title' => $title,
                    'start' => $agenda->data_hora_inicio->toIso8601String(),
                    'end' => $agenda->data_hora_fim->toIso8601String(),
                    'url' => AgendaResource::getUrl('view', ['record' => $agenda]),
                    'backgroundColor' => $agenda->cor,
                    'borderColor' => $agenda->cor,
                    'allDay' => $agenda->dia_inteiro,
                    'extendedProps' => [
                        'status' => $agenda->status,
                        'tipo' => $agenda->tipo,
                        'cliente' => $agenda->cliente?->nome,
                        'descricao' => $agenda->descricao,
                    ],
                ];
            })
            ->toArray();
    }

    public function config(): array
    {
        return [
            'locale' => 'pt-br',
            'firstDay' => 0, // Domingo
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'buttonText' => [
                'today' => 'Hoje',
                'month' => 'Mês',
                'week' => 'Semana',
                'day' => 'Dia',
                'list' => 'Lista',
            ],
            'timeZone' => 'America/Sao_Paulo',
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '22:00:00',
            'height' => 'auto',
            'navLinks' => true,
            'editable' => false,
            'selectable' => true,
            'selectMirror' => true,
            'dayMaxEvents' => 3,
            'moreLinkClick' => 'popover',
            'weekNumbers' => false,
            'nowIndicator' => true,
            'eventDisplay' => 'block',
        ];
    }

    protected function headerActions(): array
    {
        return [
            Action::make('create')
                ->label('Novo Agendamento')
                ->icon('heroicon-o-plus')
                ->url(AgendaResource::getUrl('create')),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Action::make('edit')
                ->label('Editar')
                ->icon('heroicon-o-pencil')
                ->mountUsing(
                    fn ($arguments, $form) => $form->fill([
                        'id' => $arguments['event']['id'] ?? null,
                    ])
                )
                ->action(
                    function (array $data) {
                        if (isset($data['id'])) {
                            return redirect()->to(AgendaResource::getUrl('edit', ['record' => $data['id']]));
                        }
                    }
                ),
            Action::make('view')
                ->label('Visualizar')
                ->icon('heroicon-o-eye')
                ->url(
                    fn ($arguments) => AgendaResource::getUrl('view', [
                        'record' => $arguments['event']['id'] ?? null,
                    ])
                ),
        ];
    }
}
