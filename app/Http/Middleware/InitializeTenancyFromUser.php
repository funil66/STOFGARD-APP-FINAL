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

        $tenantId = $user->tenant_id ?? $user->cadastro_id ?? null;

        if (!$tenantId) {
            if ($request->is('admin/financeiros*')) {
                abort(403);
            }

            return $next($request);
        }

        $tenant = Tenant::query()->find((string) $tenantId);

        if (!$tenant) {
            return $next($request);
        }

        $initializedHere = false;

        try {
            tenancy()->initialize($tenant);
            $initializedHere = true;
        } catch (Throwable) {
        }

        $response = $next($request);

        if ($initializedHere && tenancy()->initialized) {
            try {
                tenancy()->end();
            } catch (Throwable) {
            }
        }

        return $response;
    }
}
