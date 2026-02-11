<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Categoria;

class CadastroTipoSeeder extends Seeder
{
    public function run()
    {
        $categorias = [
            ['nome' => 'Cliente', 'slug' => 'cliente', 'cor' => 'success', 'ativo' => true],
            ['nome' => 'Lead', 'slug' => 'lead', 'cor' => 'warning', 'ativo' => true],
            ['nome' => 'Fornecedor', 'slug' => 'fornecedor', 'cor' => 'danger', 'ativo' => true],
            ['nome' => 'Parceiro', 'slug' => 'parceiro', 'cor' => 'primary', 'ativo' => true],
            ['nome' => 'Loja', 'slug' => 'loja', 'cor' => 'info', 'ativo' => true],
            ['nome' => 'Vendedor', 'slug' => 'vendedor', 'cor' => 'gray', 'ativo' => true],
            ['nome' => 'Funcionário', 'slug' => 'funcionario', 'cor' => 'gray', 'ativo' => true],
        ];

        foreach ($categorias as $cat) {
            Categoria::updateOrCreate(
                ['slug' => $cat['slug'], 'tipo' => 'cadastro_tipo'],
                $cat
            );
        }

        $this->command->info('Categorias de cadastro criadas com sucesso!');
    }
}
