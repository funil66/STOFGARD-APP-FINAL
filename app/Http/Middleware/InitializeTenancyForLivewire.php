<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Throwable;

class InitializeTenancyForLivewire extends InitializeTenancyByDomain
{
    public function handle($request, Closure $next): mixed
    {
        if ($request->routeIs('livewire.update') || $request->is('livewire/*')) {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return $next($request);
            }

            $initializedFromUser = false;

            if (function_exists('tenancy') && !tenancy()->initialized) {
                $user = Auth::user();
                $tenantId = $user?->tenant_id ?? $user?->cadastro_id ?? null;

                if ($tenantId) {
                    $tenant = Tenant::query()->find((string) $tenantId);

                    if ($tenant) {
                        try {
                            tenancy()->initialize($tenant);
                            $initializedFromUser = true;
                        } catch (Throwable) {
                        }
                    }
                }
            }

            if ($initializedFromUser) {
                $response = $next($request);

                if (tenancy()->initialized) {
                    try {
                        tenancy()->end();
                    } catch (Throwable) {
                    }
                }

                return $response;
            }

            try {
                return parent::handle($request, $next);
            } catch (TenantCouldNotBeIdentifiedOnDomainException $e) {
                // Central domains and non-tenant hosts should keep working.
                return $next($request);
            }
        }

        return $next($request);
    }
}
