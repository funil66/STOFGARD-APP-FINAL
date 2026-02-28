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

        // Limpa o banco em memória (SQLite/PgSQL) para os testes que usam RefreshDatabase
        // Se usar sqlite, não precisa do setval, mas por segurança tenta pegar
        $this->setupDefaultTenant();
    }

    protected function setupDefaultTenant(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('tenants')) {
            return;
        }

        $this->defaultTenant = Tenant::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Stofgard Test Tenant',
                'plan' => 'pro',
                'is_active' => true,
                'domain' => 'default.localhost',
            ]
        );

        // Define o tenant ativo para o contexto global (TenantScope)
        app(TenantContext::class)->set($this->defaultTenant);
    }

    protected function actingAsSuperAdmin(): self
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => true,
            'tenant_id' => $this->defaultTenant->id ?? null,
        ]);

        return $this->actingAs($user);
    }

    protected function actingAsAdmin(): self
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
            'tenant_id' => $this->defaultTenant->id ?? null,
        ]);

        return $this->actingAs($user);
    }

    protected function actingAsCliente(): self
    {
        $user = User::factory()->create([
            'is_cliente' => true,
            'tenant_id' => $this->defaultTenant->id ?? null,
        ]);

        return $this->actingAs($user);
    }
}
