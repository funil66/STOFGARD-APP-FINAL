<?php

namespace App\Policies;

use App\Models\Orcamento;
use App\Models\User;

/**
 * Policy para proteger orçamentos.
 * Bloqueia edição de orçamentos aprovados ou convertidos em OS.
 */
class OrcamentoPolicy
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
    public function view(User $user, Orcamento $orcamento): bool
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
     * Bloqueia atualização de orçamentos aprovados.
     */
    public function update(User $user, Orcamento $orcamento): bool
    {
        // Orçamentos aprovados ou convertidos são imutáveis
        $statusBloqueados = ['aprovado', 'convertido', 'finalizado'];

        if (in_array($orcamento->status, $statusBloqueados)) {
            return settings()->isAdmin($user);
        }

        return true;
    }

    /**
     * Bloqueia exclusão de orçamentos com OS vinculada.
     */
    public function delete(User $user, Orcamento $orcamento): bool
    {
        // Se tem OS vinculada, não pode deletar
        if ($orcamento->ordemServico()->exists()) {
            return false;
        }

        // Orçamentos aprovados não podem ser deletados
        if ($orcamento->status === 'aprovado') {
            return false;
        }

        return settings()->isAdmin($user);
    }

    /**
     * Permite restaurar registros soft-deleted apenas para admins.
     */
    public function restore(User $user, Orcamento $orcamento): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Força exclusão permanente apenas para admins.
     */
    public function forceDelete(User $user, Orcamento $orcamento): bool
    {
        return settings()->isAdmin($user);
    }
}
