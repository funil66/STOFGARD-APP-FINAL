<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmpresaPasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('auth.forgot-password-empresa');
    }

    public function sendCode(Request $request, EmailCodeService $emailCodeService): RedirectResponse
    {
        $data = $request->validate([
            'empresa' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $empresaDomain = $this->normalizeEmpresaDomain($data['empresa']);
        $tenant = $this->findTenantByDomain($empresaDomain);

        if ($tenant) {
            $userExists = $tenant->run(function () use ($data): bool {
                return User::where('email', $data['email'])
                    ->where('is_cliente', false)
                    ->exists();
            });

            if ($userExists) {
                $emailCodeService->sendCode(
                    email: $data['email'],
                    purpose: $this->purpose($tenant->id),
                    ttlMinutes: 15,
                    cooldownSeconds: 60,
                );
            }
        }

        return redirect()->route('empresa.password.reset.form', [
            'empresa' => $empresaDomain,
            'email' => $data['email'],
        ])->with('status', 'Se os dados estiverem corretos, enviamos um código para o seu e-mail.');
    }

    public function showResetForm(Request $request): View
    {
        return view('auth.reset-password-empresa', [
            'empresa' => (string) $request->query('empresa', ''),
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request, EmailCodeService $emailCodeService): RedirectResponse
    {
        $data = $request->validate([
            'empresa' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'codigo' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $empresaDomain = $this->normalizeEmpresaDomain($data['empresa']);
        $tenant = $this->findTenantByDomain($empresaDomain);

        if (!$tenant) {
            return back()->withErrors([
                'empresa' => 'Empresa não encontrada para o subdomínio informado.',
            ])->withInput();
        }

        $isValidCode = $emailCodeService->verifyCode(
            email: $data['email'],
            purpose: $this->purpose($tenant->id),
            code: $data['codigo'],
        );

        if (!$isValidCode) {
            return back()->withErrors([
                'codigo' => 'Código inválido ou expirado.',
            ])->withInput();
        }

        $updated = $tenant->run(function () use ($data): bool {
            $user = User::where('email', $data['email'])
                ->where('is_cliente', false)
                ->first();

            if (!$user) {
                return false;
            }

            $user->password = Hash::make($data['password']);
            $user->save();

            return true;
        });

        if (!$updated) {
            return back()->withErrors([
                'email' => 'Não encontramos usuário empresarial com este e-mail.',
            ])->withInput();
        }

        return redirect()->route('empresa.login')->with('status', 'Senha redefinida com sucesso. Faça login novamente.');
    }

    private function normalizeEmpresaDomain(string $empresa): string
    {
        $raw = Str::lower(trim($empresa));
        $raw = preg_replace('#^https?://#', '', $raw);
        $raw = explode('/', $raw)[0] ?? $raw;

        if (str_contains($raw, '.')) {
            return $raw;
        }

        $baseDomain = (string) config('domain_routing.base_domain', 'autonomia.app.br');

        return $raw . '.' . $baseDomain;
    }

    private function findTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::whereHas('domains', function ($query) use ($domain): void {
            $query->where('domain', $domain);
        })->first();
    }

    private function purpose(string $tenantId): string
    {
        return 'empresa_password_reset:' . $tenantId;
    }
}
