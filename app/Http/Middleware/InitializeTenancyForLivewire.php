<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializeTenancyForLivewire extends InitializeTenancyByDomain
{
    public function handle($request, Closure $next): mixed
    {
        if ($request->routeIs('livewire.update') || $request->is('livewire/*')) {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return $next($request);
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
