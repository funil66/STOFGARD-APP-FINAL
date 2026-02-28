<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: InitializeTenant
 *
 * Detecta o tenant ativo e o configura no TenantContext para o request atual.
 *
 * ESTRATÉGIAS DE DETECÇÃO (em ordem de prioridade):
 *
 * 1. Painel Super Admin (/super-admin): bypass total — sem filtro de tenant
 *
 * 2. Usuário autenticado com tenant_id: usa o tenant do usuário logado
 *    → Ideal para o painel admin (/admin) pós-login
 *
 * 3. Subdomain: extrai "slug" do domínio (slug.stofgard.com.br → slug)
 *    → Usado quando há multi-domínio real
 *
 * 4. Fallback: tenant "default" para ambiente local
 *    → Garante que requisições sem contexto não quebrem em dev
 */
class InitializeTenant
{
    public function __construct(private TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Painel Super Admin — bypass total
        if ($request->is('super-admin*')) {
            $this->context->enableSuperAdminBypass();

            return $next($request);
        }

        // 2. Usuário autenticado com tenant_id
        $user = $request->user();
        if ($user && !empty($user->tenant_id)) {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant && $tenant->is_active) {
                $this->context->set($tenant);

                return $next($request);
            }

            // Tenant inativo → nega acesso
            if ($tenant && !$tenant->is_active) {
                abort(403, 'Acesso da empresa suspenso. Entre em contato com o suporte.');
            }
        }

        // 3. Subdomain: slug.dominio.com
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0] ?? null;

        if ($subdomain && $subdomain !== 'www' && $subdomain !== 'localhost') {
            $tenant = Tenant::findBySlug($subdomain);
            if ($tenant) {
                $this->context->set($tenant);

                return $next($request);
            }
        }

        // 4. Fallback: tenant "default" (para dev local e dados legados)
        $default = Tenant::default();
        if ($default) {
            $this->context->set($default);
        }

        return $next($request);
    }
}
