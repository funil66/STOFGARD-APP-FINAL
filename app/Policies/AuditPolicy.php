<?php

namespace App\Policies;

use App\Models\User;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy de Auditoria Imutável.
 *
 * Os admins dos tenants podem LER o histórico, mas nenhum código
 * pode fazer UPDATE ou DELETE na tabela de audits — apenas INSERT.
 *
 * FUNDAMENTO JURÍDICO:
 * Em disputas judiciais sobre alteração de valores (orçamento, OS),
 * o log de auditoria serve como prova se for imutável. Logs deletáveis
 * são desconsiderados como prova documental.
 *
 * NOTA DE IMPLEMENTAÇÃO:
 * Esta Policy nega update/delete em nível de aplicação (Laravel Gate).
 * Para proteção em nível de banco de dados (mais forte), execute:
 *   REVOKE UPDATE, DELETE ON TABLE audits FROM {db_user};
 * no setup do banco de produção.
 */
class AuditPolicy
{
    use HandlesAuthorization;

    /**
     * Super admins podem ver tudo. Admins de tenant veem apenas auditoria do tenant.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }

    public function view(User $user, Audit $audit): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }

    /**
     * NINGUÉM pode criar auditorias manualmente — só o sistema via listener do owen-it.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * NINGUÉM pode atualizar um registro de auditoria.
     * Proteção da cadeia de custódia do log.
     */
    public function update(User $user, Audit $audit): bool
    {
        return false;
    }

    /**
     * NINGUÉM pode deletar registros de auditoria.
     * Imutabilidade é requisito para validade como prova judicial.
     */
    public function delete(User $user, Audit $audit): bool
    {
        return false;
    }

    /**
     * Hard delete também negado.
     */
    public function forceDelete(User $user, Audit $audit): bool
    {
        return false;
    }

    /**
     * Sem restauração — audit não usa SoftDeletes.
     */
    public function restore(User $user, Audit $audit): bool
    {
        return false;
    }
}
