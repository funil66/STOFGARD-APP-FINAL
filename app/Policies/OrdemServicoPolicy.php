<?php

namespace App\Policies;

use App\Models\OrdemServico;
use App\Models\User;

/**
 * Policy para proteger Ordens de Serviço.
 * Bloqueia edição de OS finalizadas ou com pagamento confirmado.
 */
class OrdemServicoPolicy
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
    public function view(User $user, OrdemServico $ordemServico): bool
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
     * Bloqueia atualização de OS finalizadas.
     */
    public function update(User $user, OrdemServico $ordemServico): bool
    {
        // OS finalizadas ou com pagamento confirmado são imutáveis
        $statusBloqueados = ['finalizada', 'pago', 'concluida'];

        if (in_array($ordemServico->status, $statusBloqueados)) {
            return settings()->isAdmin($user);
        }

        return true;
    }

    /**
     * Bloqueia exclusão de OS com financeiro vinculado.
     */
    public function delete(User $user, OrdemServico $ordemServico): bool
    {
        // Se tem financeiro vinculado, não pode deletar
        if ($ordemServico->financeiro()->exists()) {
            return false;
        }

        // OS finalizadas não podem ser deletadas
        if ($ordemServico->status === 'finalizada') {
            return false;
        }

        return settings()->isAdmin($user);
    }

    /**
     * Permite restaurar registros soft-deleted apenas para admins.
     */
    public function restore(User $user, OrdemServico $ordemServico): bool
    {
        return settings()->isAdmin($user);
    }

    /**
     * Força exclusão permanente apenas para admins.
     */
    public function forceDelete(User $user, OrdemServico $ordemServico): bool
    {
        return settings()->isAdmin($user);
    }
}
