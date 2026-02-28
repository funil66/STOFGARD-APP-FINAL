<?php

namespace App\Actions;

use App\Models\Agenda;
use App\Jobs\SyncGoogleCalendarJob;
use Illuminate\Support\Facades\Log;

/**
 * Ação: Sincronizar um evento de Agenda com o Google Calendar.
 *
 * Encapsula o disparo do Job de sincronização, separando a decisão
 * de "quando sincronizar" da implementação HTTP do Google Calendar.
 *
 * Uso:
 *   app(SyncGoogleCalendarAction::class)->sync($agenda);
 *   app(SyncGoogleCalendarAction::class)->delete($agenda);
 */
class SyncGoogleCalendarAction
{
    /**
     * Enfileira sincronização (create/update) de um evento no Google Calendar.
     * O Job tem backoff exponencial e 5 tentativas.
     */
    public function sync(Agenda $agenda): void
    {
        if (!config('services.google.client_id')) {
            Log::debug("[SyncGoogleCalendarAction] Google Calendar não configurado, pulando sync.");

            return;
        }

        SyncGoogleCalendarJob::dispatch(
            agendaId: $agenda->id,
            action: 'sync',
        )->onQueue('default');

        Log::info("[SyncGoogleCalendarAction] Agenda #{$agenda->id} enfileirada para sync.", [
            'titulo' => $agenda->titulo,
        ]);
    }

    /**
     * Enfileira remoção de um evento no Google Calendar.
     * Requer o ID do event no Google salvo na Agenda.
     */
    public function delete(Agenda $agenda, ?string $googleEventId = null): void
    {
        $eventId = $googleEventId ?? $agenda->google_event_id ?? null;

        if (!$eventId) {
            Log::debug("[SyncGoogleCalendarAction] Nenhum google_event_id para remover na agenda #{$agenda->id}");

            return;
        }

        SyncGoogleCalendarJob::dispatch(
            agendaId: $agenda->id,
            action: 'delete',
            googleEventId: $eventId,
        )->onQueue('default');

        Log::info("[SyncGoogleCalendarAction] Agenda #{$agenda->id} enfileirada para remoção do Calendar.");
    }
}
