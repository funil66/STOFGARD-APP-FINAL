<?php

namespace App\Jobs;

use App\Models\Agenda;
use App\Models\GoogleToken;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGoogleCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tentativas — tokens OAuth podem expirar e ser renovados. */
    public int $tries = 5;

    public int $timeout = 45;

    /**
     * Backoff agressivo para respeitar rate limits do Google Calendar API.
     * 1min → 3min → 5min → 10min → 20min
     */
    public function backoff(): array
    {
        return [60, 180, 300, 600, 1200];
    }

    public function __construct(
        public readonly int $agendaId,
        public readonly string $action = 'sync', // 'sync' | 'delete'
        public readonly ?string $googleEventId = null, // necessário quando action = 'delete'
    ) {
    }

    public function handle(): void
    {
        $agenda = Agenda::findOrFail($this->agendaId);

        // Resolve o token do usuário dono da agenda (sem auth context no queue worker)
        $token = GoogleToken::latest()->first();

        if (!$token) {
            Log::warning("[CalendarJob] Nenhum token Google encontrado, pulando sincronização", [
                'agenda_id' => $this->agendaId,
            ]);

            return;
        }

        $calendarService = new GoogleCalendarService($token);

        Log::info("[CalendarJob] {$this->action} - Agenda #{$this->agendaId}", [
            'attempt' => $this->attempts(),
        ]);

        if ($this->action === 'delete' && $this->googleEventId) {
            $calendarService->deleteEvent($this->googleEventId);
        } else {
            $calendarService->syncAgenda($agenda);
        }

        Log::info("[CalendarJob] Sincronização concluída para agenda #{$this->agendaId}");
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[CalendarJob] Falha na sincronização da agenda #{$this->agendaId}", [
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);
    }
}
