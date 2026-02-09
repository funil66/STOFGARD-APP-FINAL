<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para garantir que apenas administradores tenham acesso.
 * Verifica is_admin ou email na lista admin_emails das configurações.
 */
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!settings()->isAdmin(auth()->user())) {
            abort(403, 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
