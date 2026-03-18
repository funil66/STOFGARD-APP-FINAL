<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectBaselineSeeder extends Seeder
{
    /**
     * Seed mínimo de funcionamento do projeto para tenants.
     */
    public function run(): void
    {
        $this->call([
            ConfiguracaoSeeder::class,
            ConfigSeed::class,
            AuxiliaryListsSeeder::class,
            CadastroTipoSeeder::class,
            TabelaPrecosSeeder::class,
        ]);
    }
}
