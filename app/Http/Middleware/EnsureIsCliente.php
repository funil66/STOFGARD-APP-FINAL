<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures only client-role users can access the client portal.
 * Admin users are redirected to the admin panel.
 */
class EnsureIsCliente
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('filament.cliente.auth.login');
        }

        // If user is an admin but not a client, redirect to admin panel
        if ($user->is_admin && ! $user->cadastro_id) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
