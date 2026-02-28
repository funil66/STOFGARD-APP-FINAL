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
        // 1. Sempre criar o Tenant Padrão primeiro
        $this->call(TenantSeeder::class);

        // 2. Definir o Tenant no contexto para os próximos seeders
        $tenant = \App\Models\Tenant::find(1);
        if ($tenant) {
            app(\App\Services\TenantContext::class)->set($tenant);
        }

        $this->call([
            ConfiguracaoSeeder::class,
            ConfigSeed::class, // Configurações personalizadas do sistema
            AuxiliaryListsSeeder::class, // Listas auxiliares (unidades, tipos, etc.)
            CadastroTipoSeeder::class,   // Categorias de cadastro (Cliente, Lead, etc.)
            TabelaPrecosSeeder::class,
            UserSeeder::class, // Create default admin and users
            CompleteTestDataSeeder::class,
            // ClienteFactory::class, // (Se necessário)
        ]);

    }
}
