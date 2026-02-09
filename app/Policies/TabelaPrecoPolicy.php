<?php

namespace App\Policies;

use App\Models\TabelaPreco;
use App\Models\User;

class TabelaPrecoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TabelaPreco $tabelaPreco): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TabelaPreco $tabelaPreco): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TabelaPreco $tabelaPreco): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TabelaPreco $tabelaPreco): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TabelaPreco $tabelaPreco): bool
    {
        return settings()->isAdmin($user);
    }
}
