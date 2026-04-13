<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantUserController extends Controller
{
    public function create(): View
    {
        $tenants = Tenant::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('super-admin.tenant-user-create', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $tenant = Tenant::query()->find($data['tenant_id']);

        if (! $tenant) {
            return back()->withInput()->withErrors([
                'tenant_id' => 'Tenant não encontrado.',
            ]);
        }

        $initialized = false;

        try {
            tenancy()->initialize($tenant);
            $initialized = true;

            $email = strtolower(trim($data['email']));
            $exists = User::query()->where('email', $email)->exists();

            if ($exists) {
                return back()->withInput()->withErrors([
                    'email' => 'E-mail já existe neste tenant.',
                ]);
            }

            User::query()->create([
                'name' => trim($data['name']),
                'email' => $email,
                'password' => Hash::make($data['password']),
                'is_admin' => true,
                'role' => 'dono',
                'acesso_financeiro' => true,
                'email_verified_at' => now(),
            ]);

            tenancy()->end();
            $initialized = false;

            // Sincroniza também no DB Central
            $centralUser = User::where('email', $email)->first();
            if (!$centralUser) {
                User::create([
                    'name' => trim($data['name']),
                    'email' => $email,
                    'password' => Hash::make($data['password']),
                    'tenant_id' => $tenant->id,
                    'is_admin' => true,
                    'role' => 'dono',
                    'acesso_financeiro' => true,
                    'email_verified_at' => now(),
                ]);
            } elseif (empty($centralUser->tenant_id)) {
                $centralUser->update(['tenant_id' => $tenant->id]);
            }

            return back()->with('success', 'Usuário criado e sincronizado com sucesso.');
        } catch (QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'does not exist')) {
                return back()->withInput()->withErrors([
                    'tenant_id' => 'Banco do tenant não foi provisionado. Finalize o provisionamento e tente novamente.',
                ]);
            }

            return back()->withInput()->withErrors([
                'tenant_id' => 'Erro de banco ao criar usuário: ' . $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'tenant_id' => 'Erro ao criar usuário: ' . $e->getMessage(),
            ]);
        } finally {
            if ($initialized) {
                tenancy()->end();
            }
        }
    }
}
