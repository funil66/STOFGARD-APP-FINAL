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
        if (in_array($cadastro->tipo, ['cliente', 'loja', 'parceiro', 'arquiteto'])) {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                    return;
                }

                $admins = \App\Models\User::all();

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
            } catch (\Throwable) {
                // Silencia erros de notificação para não quebrar o CRUD
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
