<?php

namespace App\Observers;

use App\Models\Agenda;
use App\Models\GoogleToken;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

class AgendaObserver
{
    /**
     * Handle the Agenda "created" event.
     */
    public function created(Agenda $agenda): void
    {
        $this->syncToGoogleCalendar($agenda);
    }

    /**
     * Handle the Agenda "updated" event.
     */
    public function updated(Agenda $agenda): void
    {
        // Ignora se a atualização veio do próprio sync do Google
        if ($agenda->isDirty('google_event_id') && ! $agenda->isDirty('titulo', 'descricao', 'data_hora_inicio', 'data_hora_fim')) {
            return;
        }

        $this->syncToGoogleCalendar($agenda);
    }

    /**
     * Handle the Agenda "deleted" event.
     */
    public function deleted(Agenda $agenda): void
    {
        // Se foi soft delete e tem evento no Google, remove
        if ($agenda->google_event_id) {
            $this->deleteFromGoogleCalendar($agenda);
        }
    }

    /**
     * Handle the Agenda "restored" event.
     */
    public function restored(Agenda $agenda): void
    {
        // Quando restaurar, cria novamente no Google
        $this->syncToGoogleCalendar($agenda);
    }

    /**
     * Handle the Agenda "force deleted" event.
     */
    public function forceDeleted(Agenda $agenda): void
    {
        if ($agenda->google_event_id) {
            $this->deleteFromGoogleCalendar($agenda);
        }
    }

    /**
     * Sincroniza a agenda com o Google Calendar
     */
    protected function syncToGoogleCalendar(Agenda $agenda): void
    {
        try {
            // Busca o token do usuário autenticado
            $token = GoogleToken::where('user_id', auth()->id())->first();

            if (! $token) {
                return; // Usuário não tem Google Calendar conectado
            }

            $service = new GoogleCalendarService($token);
            $eventId = $service->syncAgenda($agenda);

            if ($eventId && ! $agenda->google_event_id) {
                // Atualiza o google_event_id sem disparar o observer novamente
                $agenda->updateQuietly(['google_event_id' => $eventId]);
            }

        } catch (\Exception $e) {
            Log::error('Erro no AgendaObserver ao sincronizar: '.$e->getMessage(), [
                'agenda_id' => $agenda->id,
            ]);
        }
    }

    /**
     * Remove a agenda do Google Calendar
     */
    protected function deleteFromGoogleCalendar(Agenda $agenda): void
    {
        try {
            $token = GoogleToken::where('user_id', auth()->id())->first();

            if (! $token) {
                return;
            }

            $service = new GoogleCalendarService($token);
            $service->deleteEvent($agenda->google_event_id);

        } catch (\Exception $e) {
            Log::error('Erro no AgendaObserver ao deletar: '.$e->getMessage(), [
                'agenda_id' => $agenda->id,
            ]);
        }
    }
}
