<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InitializeTenancyFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!function_exists('tenancy')) {
            return $next($request);
        }

        if (tenancy()->initialized) {
            return $next($request);
        }

        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $tenantId = $user->tenant_id ?? null;

        // Se é um super admin sem tenant, mandar para o /super-admin
        if ($user->is_super_admin && !$tenantId && ($request->is('admin') || $request->is('admin/*'))) {
            return redirect('/super-admin');
        }

        if (!$tenantId) {
            if ($request->is('admin') || $request->is('admin/*')) {
                // Modo resgate: se era um Super Admin impersonando um usuário defeituoso
                if (session()->has('impersonating_super_admin_id')) {
                    $superAdminId = session()->pull('impersonating_super_admin_id');
                    session()->forget('impersonated_at');

                    $superAdmin = \App\Models\User::find($superAdminId);
                    if ($superAdmin) {
                        Auth::login($superAdmin);
                        return redirect('/super-admin');
                    }
                }

                // Logoff forçado para usuários sem tenant não ficarem presos no 403
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/admin/login');
            }

            return $next($request);
        }

        $tenant = Tenant::query()->find((string) $tenantId);

        if (!$tenant) {
            if ($request->is('admin') || $request->is('admin/*')) {
                abort(403, 'Empresa não encontrada. Contate o administrador.');
            }

            return $next($request);
        }

        try {
            tenancy()->initialize($tenant);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Falha ao inicializar tenancy', [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Não faz tenancy()->end() — deixa o contexto do tenant ativo
        // durante todo o lifecycle da request (Filament precisa disso)
        return $next($request);
    }
}
