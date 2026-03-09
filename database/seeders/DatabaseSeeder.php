<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Removido WithoutModelEvents para permitir que o bootBelongsToTenant dispare e injete o tenant_id

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Verifica se está rodando no contexto de um Tenant (via tenants:seed)
        if (tenancy()->initialized) {
            $this->call([
                ConfiguracaoSeeder::class,
                ConfigSeed::class, // Configurações personalizadas do sistema
                AuxiliaryListsSeeder::class, // Listas auxiliares (unidades, tipos, etc.)
                CadastroTipoSeeder::class,   // Categorias de cadastro (Cliente, Lead, etc.)
                TabelaPrecosSeeder::class,
                CompleteTestDataSeeder::class,
            ]);
        } else {
            // Contexto Central (Admin SaaS)
            // 1. Sempre criar o Tenant Padrão primeiro
            $this->call(TenantSeeder::class);

            // 2. Definir o Tenant no contexto para os próximos seeders globais
            $tenant = \App\Models\Tenant::find(1);
            if ($tenant) {
                app(\App\Services\TenantContext::class)->set($tenant);
            }

            $this->call([
                UserSeeder::class, // Create default admin and users
            ]);
        }

    }
}
