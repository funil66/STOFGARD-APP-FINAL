<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Exceptions\NotASubdomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Throwable;

class InitializeTenancyForLivewire extends InitializeTenancyByDomain
{
    public function handle($request, Closure $next): mixed
    {
        // 1. Primeiro tenta inicializar pelo domínio/subdomínio da requisição
        try {
            return parent::handle($request, $next);
        } catch (TenantCouldNotBeIdentifiedOnDomainException|NotASubdomainException $e) {
            // Se falhou (ex: estamos no domínio central, mas a requisição é do livewire)
            // vamos tentar deduzir o tenant a partir do usuário logado.
        }

        // 2. Se caiu aqui, é porque não inicializou pelo domínio da URL.
        if (
            $request->routeIs('livewire.update') || 
            $request->is('livewire/*') || 
            $request->is('super-admin') || 
            $request->is('super-admin/*') || 
            $request->is('admin') || 
            $request->is('admin/*')
        ) {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return $next($request);
            }

            $initializedFromUser = false;

            if (function_exists('tenancy') && !tenancy()->initialized) {
                // Como ainda estamos no domínio central (o parent falhou e não alterou a DB connection),
                // o Auth::user() vai buscar do banco central, o que é CORRETO para usuários do super-admin!
                \Illuminate\Support\Facades\Log::info("[InitializeTenancyForLivewire] Checking user: ", ["route" => $request->path(), "user" => Auth::user()?->toArray(), "session_id" => session()->getId()]);
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
        }

        // Se chegou até aqui sem iniciar o tenant, segue a requisição normal (contexto central)
        return $next($request);
    }
}
