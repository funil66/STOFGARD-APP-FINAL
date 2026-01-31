<?php

namespace Database\Seeders;

use App\Models\Estoque;
use Illuminate\Database\Seeder;

class EstoqueSeeder extends Seeder
{
    public function run(): void
    {
        // Usando apenas as colunas que sabemos que existem
        $itens = [
            [
                'item' => 'Impermeabilizante',
                'quantidade' => 60, // 3 galões de 20L
                'unidade' => 'litros',
                'minimo_alerta' => 20, // 1 galão
            ],
            [
                'item' => 'Frotador',
                'quantidade' => 40, // 2 galões de 20L
                'unidade' => 'litros',
                'minimo_alerta' => 20, // 1 galão
            ],
        ];

        foreach ($itens as $item) {
            Estoque::firstOrCreate(
                ['item' => $item['item']],
                $item
            );
        }
    }
}
