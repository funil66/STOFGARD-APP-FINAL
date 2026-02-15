<?php

namespace App\Observers;

use App\Models\Agenda;
use App\Models\OrdemServico;
use Filament\Notifications\Notification;

class AgendaObserver
{
    /**
     * Handle the Agenda "created" event.
     */
    public function created(Agenda $agenda): void
    {
        $this->syncOrdemServico($agenda);
    }

    /**
     * Handle the Agenda "updated" event.
     */
    public function updated(Agenda $agenda): void
    {
        $this->syncOrdemServico($agenda);
    }

    /**
     * Handle the Agenda "deleted" event.
     */
    public function deleted(Agenda $agenda): void
    {
        // Optional: clear OS date if agenda is deleted? 
        // Better to leave it to avoid data loss unless requested.
    }

    /**
     * Synchronize Agenda date with OrdemServico
     */
    protected function syncOrdemServico(Agenda $agenda): void
    {
        // Only proceed if there is a linked OS and the date has been set/changed
        if ($agenda->ordem_servico_id && $agenda->data_hora_inicio) {
            $os = OrdemServico::find($agenda->ordem_servico_id);

            if ($os) {
                // Check if date is actually different to avoid infinite loops if OS also updates Agenda (though OS doesn't seem to observe back yet)
                $novaData = \Carbon\Carbon::parse($agenda->data_hora_inicio);

                if (!$os->data_prevista || $os->data_prevista->ne($novaData)) {
                    $os->data_prevista = $novaData;
                    // Save quietly to avoid triggering other observers if unnecessary, 
                    // but if OS has observers that need to run, standard save() is better.
                    // safely use save() as OS observer doesn't seem to update Agenda.
                    $os->save();

                    Notification::make()
                        ->title('Sincronização Automática')
                        ->body("A data prevista da OS #{$os->numero_os} foi atualizada conforme a Agenda.")
                        ->success()
                        ->sendToDatabase(\App\Models\User::find($agenda->criado_por) ?? \App\Models\User::first());
                }
            }
        }
    }
}
