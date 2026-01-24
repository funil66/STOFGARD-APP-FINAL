<?php

namespace App\Observers;

use App\Models\Agenda;
use Illuminate\Support\Facades\Log;

class AgendaObserver
{
    /**
     * Handle the Agenda "created" event.
     */
    public function created(Agenda $agenda): void
    {
        // Removed Google Calendar sync
    }

    /**
     * Handle the Agenda "updated" event.
     */
    public function updated(Agenda $agenda): void
    {
        // Removed Google Calendar sync
    }

    /**
     * Handle the Agenda "deleted" event.
     */
    public function deleted(Agenda $agenda): void
    {
        // Removed Google Calendar sync
    }

    /**
     * Handle the Agenda "restored" event.
     */
    public function restored(Agenda $agenda): void
    {
        // Removed Google Calendar sync
    }

    /**
     * Handle the Agenda "force deleted" event.
     */
    public function forceDeleted(Agenda $agenda): void
    {
        // Removed Google Calendar sync
    }
}
