<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
