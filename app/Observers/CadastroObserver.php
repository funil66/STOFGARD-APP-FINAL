<?php

namespace App\Observers;

use App\Models\Cadastro;
use App\Services\NotificationService;
use App\Models\User;

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
                \App\Services\NotificationService::success(
                    $admin,
                    'Novo Cadastro: ' . $cadastro->nome,
                    "Um novo {$cadastro->tipo} foi registrado no sistema.",
                    'cadastro',
                    'heroicon-o-user-plus',
                    '/admin/cadastros/' . $cadastro->id . '/edit',
                    'Ver Cadastro'
                );
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
