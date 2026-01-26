<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $servicos = [
            // SOFÁS (Higienização)
            ['nome' => 'Higienização Sofá Retrátil 2 Lugares', 'preco_custo' => 30.00, 'preco_venda' => 180.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Sofá Retrátil 3 Lugares', 'preco_custo' => 40.00, 'preco_venda' => 220.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Sofá Retrátil 4 Lugares', 'preco_custo' => 50.00, 'preco_venda' => 280.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Sofá de Canto (5 Lugares)', 'preco_custo' => 60.00, 'preco_venda' => 300.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Sofá de Canto (6 Lugares)', 'preco_custo' => 70.00, 'preco_venda' => 350.00, 'unidade' => 'un'],

            // SOFÁS (Impermeabilização)
            ['nome' => 'Impermeabilização Sofá 2 Lugares', 'preco_custo' => 80.00, 'preco_venda' => 350.00, 'unidade' => 'un'],
            ['nome' => 'Impermeabilização Sofá 3 Lugares', 'preco_custo' => 100.00, 'preco_venda' => 450.00, 'unidade' => 'un'],
            ['nome' => 'Impermeabilização Sofá 4 Lugares', 'preco_custo' => 120.00, 'preco_venda' => 550.00, 'unidade' => 'un'],
            
            // COLCHÕES
            ['nome' => 'Higienização Colchão Solteiro', 'preco_custo' => 15.00, 'preco_venda' => 120.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Colchão Casal Padrão', 'preco_custo' => 20.00, 'preco_venda' => 160.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Colchão Queen Size', 'preco_custo' => 25.00, 'preco_venda' => 200.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Colchão King Size', 'preco_custo' => 30.00, 'preco_venda' => 250.00, 'unidade' => 'un'],
            
            // CADEIRAS E POLTRONAS
            ['nome' => 'Higienização Cadeira de Jantar (Assento)', 'preco_custo' => 2.00, 'preco_venda' => 25.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Cadeira de Jantar (Assento + Encosto)', 'preco_custo' => 4.00, 'preco_venda' => 40.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Poltrona Simples', 'preco_custo' => 10.00, 'preco_venda' => 90.00, 'unidade' => 'un'],
            ['nome' => 'Higienização Poltrona do Papai (Reclinável)', 'preco_custo' => 15.00, 'preco_venda' => 120.00, 'unidade' => 'un'],
            
            // VEÍCULOS
            ['nome' => 'Higienização Interna Completa (Carro P)', 'preco_custo' => 40.00, 'preco_venda' => 250.00, 'unidade' => 'sv'],
            ['nome' => 'Higienização Interna Completa (SUV)', 'preco_custo' => 50.00, 'preco_venda' => 350.00, 'unidade' => 'sv'],
            ['nome' => 'Higienização Teto Veicular', 'preco_custo' => 10.00, 'preco_venda' => 80.00, 'unidade' => 'un'],
            
            // TAPETES
            ['nome' => 'Lavagem de Tapete (Pelo Baixo)', 'preco_custo' => 5.00, 'preco_venda' => 25.00, 'unidade' => 'm2'],
            ['nome' => 'Lavagem de Tapete (Pelo Alto)', 'preco_custo' => 8.00, 'preco_venda' => 35.00, 'unidade' => 'm2'],
        ];

        foreach ($servicos as $servico) {
            Produto::firstOrCreate(
                ['nome' => $servico['nome']],
                $servico
            );
        }
    }
}
