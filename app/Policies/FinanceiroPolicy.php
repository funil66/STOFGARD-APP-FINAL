<?php

namespace App\Policies;

use App\Models\Financeiro;
use App\Models\User;

/**
 * Policy para proteger registros financeiros.
 * Bloqueia edição/exclusão de registros pagos ou fiscais.
 */
class FinanceiroPolicy
{
    /**
     * Permite visualização para todos os usuários autenticados.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Permite visualização de um registro específico.
     */
    public function view(User $user, Financeiro $financeiro): bool
    {
        return true;
    }

    /**
     * Permite criação para todos os usuários autenticados.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Bloqueia atualização de registros pagos (apenas admin pode).
     */
    public function update(User $user, Financeiro $financeiro): bool
    {
        // Registros pagos são imutáveis (exceto para admins)
        if ($financeiro->status === 'pago') {
            return settings()->isAdmin($user);
        }

        return true;
    }

    /**
     * Bloqueia exclusão de registros pagos ou com comprovante.
     */
    public function delete(User $user, Financeiro $financeiro): bool
    {
        // Nunca permitir deletar registros pagos
        if ($financeiro->status === 'pago') {
            return false;
        }

        // Registros com comprovante fiscal não podem ser excluídos
        if (!empty($financeiro->comprovante)) {
            return false;
        }

        // Para registros pendentes, apenas admin pode deletar
        return settings()->isAdmin($user);
    }

    /**
     * Permite restaurar registros soft-deleted apenas para admins.
     */
    public function restore(User $user, Financeiro $financeiro): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Força exclusão permanente apenas para admins.
     */
    public function forceDelete(User $user, Financeiro $financeiro): bool
    {
        return settings()->isAdmin($user);
    }
}
