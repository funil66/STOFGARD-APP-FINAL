<?php

namespace App\Observers;

use App\Models\ListaDesejo;
use Filament\Notifications\Notification;

class ListaDesejoObserver
{
    /**
     * Handle the ListaDesejo "created" event.
     */
    public function created(ListaDesejo $listaDesejo): void
    {
        //
    }

    /**
     * Handle the ListaDesejo "updated" event.
     */
    public function updated(ListaDesejo $listaDesejo): void
    {
        // Verifica se a data prevista está próxima
        $this->verificarDataPrevista($listaDesejo);
    }

    /**
     * Verifica se a data prevista de compra está próxima ou vencida
     */
    protected function verificarDataPrevista(ListaDesejo $listaDesejo): void
    {
        if (! $listaDesejo->data_prevista_compra) {
            return;
        }

        if ($listaDesejo->status === 'comprado') {
            return;
        }

        if (!auth()->user() || !\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            return;
        }

        try {
            $hoje = \Carbon\Carbon::now()->startOfDay();
            $dataPrevista = \Carbon\Carbon::parse($listaDesejo->data_prevista_compra)->startOfDay();
            $diasRestantes = $hoje->diffInDays($dataPrevista, false);

            if ($diasRestantes < 0) {
                Notification::make()
                    ->title('⚠️ DATA DE COMPRA VENCIDA!')
                    ->body("{$listaDesejo->nome} - Previsão: ".\Carbon\Carbon::parse($listaDesejo->data_prevista_compra)->format('d/m/Y'))
                    ->danger()
                    ->persistent()
                    ->sendToDatabase(auth()->user());
            } elseif ($diasRestantes <= 1) {
                Notification::make()
                    ->title('📅 COMPRA URGENTE!')
                    ->body("{$listaDesejo->nome} - Previsão: ".\Carbon\Carbon::parse($listaDesejo->data_prevista_compra)->format('d/m/Y'))
                    ->warning()
                    ->persistent()
                    ->sendToDatabase(auth()->user());
            } elseif ($diasRestantes <= 7) {
                Notification::make()
                    ->title('📋 Compra Próxima')
                    ->body("{$listaDesejo->nome} em {$diasRestantes} dia(s)")
                    ->info()
                    ->sendToDatabase(auth()->user());
            }
        } catch (\Throwable) {
            // Não bloqueia a atualização do registro por falha na notificação
        }
    }
}
