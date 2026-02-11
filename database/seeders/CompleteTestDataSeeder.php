<?php

namespace Database\Seeders;

use App\Models\Cadastro;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompleteTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Cria Tipos/Categorias (necessário para cores e badges)
        $this->call(CadastroTipoSeeder::class);

        // 1. Cria Parceiros (Lojas e Arquitetos)
        $lojas = Cadastro::factory()->count(5)->loja()->create();
        $arquitetos = Cadastro::factory()->count(10)->arquiteto()->create();

        $this->command->info('🏪 Lojas e Arquitetos criados.');

        // 2. Cria Clientes Finais (vinculados a nada por enquanto)
        Cadastro::factory()->count(50)->create();

        $this->command->info('👥 50 Clientes criados.');

    }
}
