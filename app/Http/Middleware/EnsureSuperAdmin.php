<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Garante que apenas Super Admins acessem o painel /super-admin.
 *
 * Super Admin = usuário com is_admin = true E marcado como super_admin na tabela users.
 * Para adicionar super admin, execute no tinker:
 *   User::find($id)->update(['is_super_admin' => true]);
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('filament.super-admin.auth.login');
        }

        $user = auth()->user();

        // Verificação tripla: autenticado + admin + super_admin
        if (!$user->is_admin || !($user->is_super_admin ?? false)) {
            abort(403, 'Acesso restrito ao painel de super administradores.');
        }

        return $next($request);
    }
}
