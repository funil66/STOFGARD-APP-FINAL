<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;

class StofgardServicesSeeder extends Seeder
{
    public function run(): void
    {
        $servicos = [
            // --- HIGIENIZAÇÃO DE ESTOFADOS ---
            ['categoria' => 'Estofados', 'nome' => 'Higienização Sofá Retrátil 2 Lugares', 'preco_custo' => 45.00, 'preco_venda' => 220.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Sofá Retrátil 3 Lugares', 'preco_custo' => 60.00, 'preco_venda' => 280.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Sofá Retrátil 4 Lugares', 'preco_custo' => 75.00, 'preco_venda' => 350.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Sofá de Canto (5 Lugares)', 'preco_custo' => 90.00, 'preco_venda' => 400.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Sofá Living 2 Lugares', 'preco_custo' => 35.00, 'preco_venda' => 180.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Poltrona Simples', 'preco_custo' => 15.00, 'preco_venda' => 90.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Poltrona do Papai/Reclinável', 'preco_custo' => 25.00, 'preco_venda' => 140.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Cadeira de Jantar (Assento)', 'preco_custo' => 5.00, 'preco_venda' => 35.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Cadeira de Jantar (Completa)', 'preco_custo' => 8.00, 'preco_venda' => 50.00, 'unidade' => 'un'],
            ['categoria' => 'Estofados', 'nome' => 'Higienização Puff Pequeno', 'preco_custo' => 5.00, 'preco_venda' => 40.00, 'unidade' => 'un'],

            // --- IMPERMEABILIZAÇÃO (Blindagem) ---
            ['categoria' => 'Impermeabilização', 'nome' => 'Blindagem Sofá 2 Lugares', 'preco_custo' => 80.00, 'preco_venda' => 380.00, 'unidade' => 'un'],
            ['categoria' => 'Impermeabilização', 'nome' => 'Blindagem Sofá 3 Lugares', 'preco_custo' => 120.00, 'preco_venda' => 480.00, 'unidade' => 'un'],
            ['categoria' => 'Impermeabilização', 'nome' => 'Blindagem Sofá 4 Lugares', 'preco_custo' => 150.00, 'preco_venda' => 580.00, 'unidade' => 'un'],
            ['categoria' => 'Impermeabilização', 'nome' => 'Blindagem Cadeira de Jantar', 'preco_custo' => 15.00, 'preco_venda' => 60.00, 'unidade' => 'un'],

            // --- COLCHÕES (Saúde do Sono) ---
            ['categoria' => 'Colchões', 'nome' => 'Higienização Colchão Solteiro', 'preco_custo' => 20.00, 'preco_venda' => 140.00, 'unidade' => 'un'],
            ['categoria' => 'Colchões', 'nome' => 'Higienização Colchão Casal', 'preco_custo' => 30.00, 'preco_venda' => 180.00, 'unidade' => 'un'],
            ['categoria' => 'Colchões', 'nome' => 'Higienização Colchão Queen', 'preco_custo' => 40.00, 'preco_venda' => 220.00, 'unidade' => 'un'],
            ['categoria' => 'Colchões', 'nome' => 'Higienização Colchão King', 'preco_custo' => 50.00, 'preco_venda' => 280.00, 'unidade' => 'un'],

            // --- AUTOMOTIVO ---
            ['categoria' => 'Automotivo', 'nome' => 'Higienização Bancos (Carro Passeio)', 'preco_custo' => 40.00, 'preco_venda' => 250.00, 'unidade' => 'sv'],
            ['categoria' => 'Automotivo', 'nome' => 'Higienização Completa (Teto+Carpete+Bancos)', 'preco_custo' => 80.00, 'preco_venda' => 450.00, 'unidade' => 'sv'],
            ['categoria' => 'Automotivo', 'nome' => 'Hidratação de Couro (Bancos)', 'preco_custo' => 30.00, 'preco_venda' => 180.00, 'unidade' => 'sv'],

            // --- TAPETES ---
            ['categoria' => 'Tapetes', 'nome' => 'Lavagem Tapete Pelo Curto', 'preco_custo' => 8.00, 'preco_venda' => 30.00, 'unidade' => 'm2'],
            ['categoria' => 'Tapetes', 'nome' => 'Lavagem Tapete Pelo Longo/Shaggy', 'preco_custo' => 12.00, 'preco_venda' => 45.00, 'unidade' => 'm2'],
        ];

        foreach ($servicos as $servico) {
            $dados = $servico;
            unset($dados['categoria']);

            Produto::updateOrCreate(
                ['nome' => $servico['nome']],
                $dados
            );
        }
    }
}
