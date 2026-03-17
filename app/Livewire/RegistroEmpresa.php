<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantTemplateProvisioner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Multi-step company registration form.
 *
 * Step 1: Company data (name, CNPJ, email, phone)
 * Step 2: Admin user (name, email, password)
 * Step 3: Plan selection
 * Step 4: Confirmation → ProvisionTenantJob
 */
class RegistroEmpresa extends Component
{
    public int $step = 1;

    // Step 1
    public string $empresa_nome = '';
    public string $empresa_cnpj = '';
    public string $empresa_email = '';
    public string $empresa_telefone = '';
    public string $dominio_personalizado = '';

    // Step 2
    public string $admin_nome = '';
    public string $admin_email = '';
    public string $admin_password = '';
    public string $admin_password_confirmation = '';

    // Step 3
    public string $plano = 'pro';

    // Result
    public bool $concluido = false;
    public string $dominio_criado = '';

    protected function rules(): array
    {
        return match ($this->step) {
            1 => [
                'empresa_nome' => 'required|string|max:255',
                'empresa_cnpj' => 'nullable|string|max:20',
                'empresa_email' => 'required|email|max:255',
                'empresa_telefone' => 'nullable|string|max:20',
                'dominio_personalizado' => ['nullable', 'string', 'max:255', 'regex:/^(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/'],
            ],
            2 => [
                'admin_nome' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:users,email',
                'admin_password' => 'required|string|min:8|confirmed',
            ],
            3 => [
                'plano' => 'required|in:free,pro,elite',
            ],
            default => [],
        };
    }

    public function nextStep(): void
    {
        $this->validate();
        $this->step = min($this->step + 1, 4);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function confirmar(): void
    {
        $slug = Str::slug($this->empresa_nome);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $trialDays = (int) env('TRIAL_DAYS', 14);

        // Create tenant (triggers CreateDatabase + MigrateDatabase + SeedDatabase via TenancyServiceProvider)
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => $this->empresa_nome,
            'slug' => $slug,
            'plan' => $this->plano,
            'is_active' => true,
            'status_pagamento' => 'trial',
            'trial_termina_em' => now()->addDays($trialDays),
            'max_users' => match ($this->plano) {
                'free' => 3,
                'pro' => 10,
                'elite' => 999,
                default => 5,
            },
            'max_orcamentos_mes' => match ($this->plano) {
                'free' => (int) env('PLAN_FREE_OS_LIMIT', 30),
                default => 0, // unlimited
            },
            'settings' => [
                'timezone' => 'America/Sao_Paulo',
                'currency' => 'BRL',
                'locale' => 'pt_BR',
            ],
        ]);

        // Create domain (custom domain has priority)
        $baseDomain = env('TENANT_BASE_DOMAIN', env('APP_DOMAIN', parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'));
        $domain = $this->resolveDomain($slug, $baseDomain);

        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        // Garante baseline visual/funcional idêntico ao tenant referência (STOFGARD)
        app(TenantTemplateProvisioner::class)->apply($tenant);

        // Create admin user inside tenant context
        $tenant->run(function () {
            User::create([
                'name' => $this->admin_nome,
                'email' => $this->admin_email,
                'password' => Hash::make($this->admin_password),
                'is_admin' => true,
                'tenant_id' => tenant('id'),
            ]);
        });

        $this->dominio_criado = $domain;
        $this->concluido = true;
    }

    protected function resolveDomain(string $slug, string $baseDomain): string
    {
        $customDomain = trim(strtolower($this->dominio_personalizado));

        if ($customDomain !== '') {
            $customDomain = preg_replace('#^https?://#', '', $customDomain);
            $customDomain = explode('/', $customDomain)[0];

            return $customDomain;
        }

        return str_contains($slug, '.')
            ? $slug
            : $slug . '.' . $baseDomain;
    }

    public function render()
    {
        return view('livewire.registro-empresa')
            ->layout('components.layouts.guest');
    }
}
