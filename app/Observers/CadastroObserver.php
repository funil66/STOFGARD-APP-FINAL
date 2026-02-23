<?php

namespace App\Observers;

use App\Models\Cadastro;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CadastroObserver
{
    /**
     * Handle the Cadastro "created" event.
     */
    public function created(Cadastro $cadastro): void
    {
        // Notificar apenas se for um cliente/lead ou parceiro relevante
        if (in_array($cadastro->tipo, ['cliente', 'loja', 'parceiro', 'arquiteto'])) {
            $admins = \App\Models\User::all(); // TODO: Filtrar apenas admins ou interessados

            foreach ($admins as $admin) {
                Notification::make()
                    ->title('Novo Cadastro: ' . $cadastro->nome)
                    ->body("Um novo {$cadastro->tipo} foi registrado no sistema.")
                    ->icon('heroicon-o-user-plus')
                    ->success()
                    ->actions([
                        Action::make('view')
                            ->label('Ver Cadastro')
                            ->button()
                            ->url('/admin/cadastros/' . $cadastro->id . '/edit')
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($admin);
            }
        }
    }

    /**
     * Handle the Cadastro "updated" event.
     */
    public function updated(Cadastro $cadastro): void
    {
        //
    }

    /**
     * Handle the Cadastro "deleted" event.
     */
    public function deleted(Cadastro $cadastro): void
    {
        //
    }

    /**
     * Handle the Cadastro "restored" event.
     */
    public function restored(Cadastro $cadastro): void
    {
        //
    }

    /**
     * Handle the Cadastro "force deleted" event.
     */
    public function forceDeleted(Cadastro $cadastro): void
    {
        //
    }
}
