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
            TabelaPrecosSeeder::class,
            UserSeeder::class, // Create default admin and users
            // ClienteFactory::class, // (Se necessário)
        ]);
    }
}
