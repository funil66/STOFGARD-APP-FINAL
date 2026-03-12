<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected Tenant $defaultTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDefaultTenant();
    }

    // Hook executado pelo Trait RefreshDatabase logo após "migrate:fresh"
    protected function afterRefreshingDatabase()
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    }

    protected function setupDefaultTenant(): void
    {
        // Desativando separação em arquivos (banco híbrido in-memory)
        config([
            'tenancy.database.managers.sqlite' => null,
            'tenancy.database.managers.mysql' => null,
            'tenancy.database.managers.pgsql' => null,
        ]);

        // O SEGREDO DO SQLITE IN-MEMORY MULTI-TENANT: 
        // Impedimos que o bootstrapper crie uma NOVA conexão ':memory:' (pois gera banco vazio limpo)
        $bootstrappers = config('tenancy.bootstrappers');
        $filtered = array_filter($bootstrappers, fn($b) => $b !== \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class);
        config(['tenancy.bootstrappers' => array_values($filtered)]);

        if (!\Illuminate\Support\Facades\Schema::hasTable('tenants')) {
            $this->artisan('migrate', [
                '--path' => ['database/migrations', 'database/migrations/tenant'],
                '--force' => true
            ]);
        }

        $this->defaultTenant = Tenant::withoutEvents(function () {
            return Tenant::firstOrCreate(
                ['id' => 'foo'],
                [
                    'name' => 'Autonomia Ilimitada Test Tenant',
                    'is_active' => true,
                    'data_vencimento' => '2099-12-31',
                    'limite_os_mes' => 9999,
                ]
            );
        });

        if ($this->defaultTenant->domains()->count() === 0) {
            $this->defaultTenant->domains()->create(['domain' => 'localhost']);
        }

        tenancy()->initialize($this->defaultTenant);
    }

    protected function actingAsSuperAdmin(): self
    {
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        return $this->actingAs($user);
    }

    protected function actingAsAdmin(): self
    {
        if (!\Spatie\Permission\Models\Role::where('name', 'dono')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'dono', 'guard_name' => 'web']);
        }

        $user = User::factory()->create([
            'name' => 'Admin test',
            'is_admin' => true,
        ]);
        $user->assignRole('dono');

        return $this->actingAs($user);
    }

    protected function actingAsCliente(): self
    {
        $user = User::factory()->create([
            'is_cliente' => true,
        ]);
        return $this->actingAs($user);
    }
}
