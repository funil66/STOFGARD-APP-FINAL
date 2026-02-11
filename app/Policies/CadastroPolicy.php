<?php

namespace App\Policies;

use App\Models\User;

class CadastroPolicy
{
    /**
     * Verifica se usuário pode visualizar lista
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Verifica se usuário pode visualizar registros
     */
    public function view(User $user, $model): bool
    {
        return true;
    }

    /**
     * Verifica se usuário pode atualizar registros
     */
    public function update(User $user, $model): bool
    {
        return true;
    }

    /**
     * Verifica se usuário pode deletar registros
     */
    public function delete(User $user, $model): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Verifica se usuário pode baixar arquivos
     */
    public function restore(User $user, $model): bool
    {
        return true;
    }

    public function forceDelete(User $user, $model): bool
    {
        return settings()->isAdmin($user);
    }
}

