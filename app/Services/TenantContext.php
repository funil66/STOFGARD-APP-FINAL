<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * Singleton: TenantContext
 *
 * Mantém o tenant ativo durante o ciclo de vida do request.
 * Usado pelo TenantScope para filtrar queries automaticamente.
 *
 * REGISTRADO em AppServiceProvider como singleton.
 *
 * NUNCA persista estado entre requests (o container do Laravel garante isso,
 * desde que octane não seja usado sem reset de tenant por request).
 *
 * USO:
 *   app(TenantContext::class)->set($tenant);   // no middleware
 *   app(TenantContext::class)->get();           // no scope
 *   app(TenantContext::class)->id();            // nas queries
 *   app(TenantContext::class)->isSuperAdmin();  // para bypass do scope
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    private bool $superAdminBypass = false;

    // ──────────────────────────────────────────
    // Setter / Getter
    // ──────────────────────────────────────────

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->superAdminBypass = false;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function isSet(): bool
    {
        return $this->tenant !== null || $this->superAdminBypass;
    }

    // ──────────────────────────────────────────
    // Super Admin bypass — sem filtro de tenant
    // ──────────────────────────────────────────

    /**
     * Ativa o bypass de tenant para o Super Admin (acesso global a todos os dados).
     * Chame apenas NO CONTEXTO DO PAINEL /super-admin.
     */
    public function enableSuperAdminBypass(): void
    {
        $this->superAdminBypass = true;
        $this->tenant = null;
    }

    public function isSuperAdminBypass(): bool
    {
        return $this->superAdminBypass;
    }

    // ──────────────────────────────────────────
    // Reset (usado em testes e Octane)
    // ──────────────────────────────────────────

    public function reset(): void
    {
        $this->tenant = null;
        $this->superAdminBypass = false;
    }
}
