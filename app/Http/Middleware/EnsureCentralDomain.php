<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('tenant') && tenant()) {
            abort(403, 'Acesso negado neste domínio.');
        }

        return $next($request);
    }
}
