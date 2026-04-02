<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CentralLoginController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Credenciais inválidas.']);
        }

        // Se for super admin e não tiver tenant, manda pro painel central
        if ($user->is_super_admin && !$user->tenant_id) {
            Auth::login($user);
            return redirect('/super-admin');
        }

        if (!$user->tenant_id) {
            return back()->withErrors(['email' => 'Usuário não vinculado a uma empresa.']);
        }

        // Buscar subdomínio/domínio do tenant
        $tenant = \App\Models\Tenant::find($user->tenant_id);
        
        if (!$tenant) {
            return back()->withErrors(['email' => 'Empresa inativa ou não encontrada.']);
        }

        // Gera um token de acesso rápido válido por 30 segundos usando o Cache central
        $token = Str::random(64);
        Cache::store(config('cache.default'))->put('central_auth_token_' . $token, $user->email, now()->addSeconds(30));

        $domain = $tenant->domains()->first();
        if (!$domain) {
            // Fallback se não tiver domain model mapeado: {tenant_id}.autonomia.app.br
            $baseDomain = config('tenancy.central_domains')[0] ?? 'autonomia.app.br';
            $url = 'https://' . $tenant->id . '.' . $baseDomain;
        } else {
            // Em dev local, não usar https hardcoded
            $scheme = app()->isLocal() ? 'http://' : 'https://';
            $url = $scheme . $domain->domain;
        }

        return redirect()->away($url . '/admin/login?token=' . $token);
    }
}
