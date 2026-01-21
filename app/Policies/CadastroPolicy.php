<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Parceiro;

class CadastroPolicy
{
    public function view(User $user, $model): bool
    {
        // Viewing public pages handled elsewhere; for admin UI require admin
        return ($user->is_admin == true) || ($user->email === 'allisson@stofgard.com.br');
    }

    public function update(User $user, $model): bool
    {
        return ($user->is_admin == true) || ($user->email === 'allisson@stofgard.com.br');
    }

    public function delete(User $user, $model): bool
    {
        return ($user->is_admin == true) || ($user->email === 'allisson@stofgard.com.br');
    }

    public function download(User $user, $model): bool
    {
        // downloads require authentication (not necessarily admin)
        return $user !== null;
    }
}
