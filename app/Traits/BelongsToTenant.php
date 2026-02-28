<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use App\Services\TenantContext;

/**
 * Trait: BelongsToTenant
 *
 * Adicionar este trait a TODOS os Models de negócio (Cadastro, Orcamento, OS, etc.)
 * para aplicar isolamento multi-tenant automático.
 *
 * O que o trait faz:
 * 1. Registra o TenantScope global (filtra queries por tenant_id)
 * 2. Preenche tenant_id automaticamente no evento `creating`
 *
 * USO:
 *   class Cadastro extends Model {
 *       use BelongsToTenant;
 *   }
 *
 * BYPASS EM TESTES:
 *   Cadastro::withoutGlobalScope(TenantScope::class)->all();
 *   // ou registre um TenantContext com super admin bypass no setUp()
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // 1. Registra o scope global para filtrar queries
        static::addGlobalScope(new TenantScope());

        // 2. Preenche tenant_id automaticamente ao criar
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantId = app(TenantContext::class)->id();

                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Relação: cada registro pertence a um Tenant.
     */
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Escapa o TenantScope para este model em um closure.
     * Útil em Commands e relatórios globais do Super Admin.
     *
     * Exemplo:
     *   Cadastro::withAllTenants(fn() => Cadastro::count());
     */
    public static function withAllTenants(callable $callback): mixed
    {
        return static::withoutGlobalScope(TenantScope::class)->tap(fn() => $callback());
    }
}
