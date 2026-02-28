<?php

namespace App\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope: TenantScope
 *
 * Injetado automaticamente via BelongsToTenant trait em todos os Models de negócio.
 * Adiciona WHERE tenant_id = ? a TODAS as queries Eloquent — transparente para o código
 * existente (Resources do Filament, Actions, Services não precisam mudar).
 *
 * BYPASS:
 * - Super Admin panel: TenantContext::enableSuperAdminBypass()
 * - Tinker / CLI isolados: usar withoutGlobalScope(TenantScope::class)
 * - Testes: usar a trait WithoutTenantScope ou mockTenantContext()
 *
 * CREATING event:
 * O tenant_id é preenchido automaticamente pelo BelongsToTenant trait.
 * Este Scope não interfere em inserts.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        // Super admin bypass: sem filtro — acessa todos os tenants
        if ($context->isSuperAdminBypass()) {
            return;
        }

        $tenantId = $context->id();

        // Tenant identificado: aplica filtro
        if ($tenantId !== null) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);

            return;
        }

        // Nenhum tenant no contexto (e.g. comando CLI, schedule) — não filtra.
        // Em produção, o middleware garante que sempre haverá tenant no contexto HTTP.
        // Para CLI com dados sensíveis, use withoutGlobalScope(TenantScope::class) explicitamente.
    }
}
