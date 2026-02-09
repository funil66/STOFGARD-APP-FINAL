<?php

namespace Database\Seeders;

use App\Models\Estoque;
use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $produtos = [
            [
                'nome' => 'Impermeabilizante',
                'descricao' => 'Produto líquido para impermeabilização de estofados. Galão de 20L.',
                'preco_custo' => 250.00,
                'preco_venda' => 350.00,
                'unidade' => 'L',
                'estoque_inicial' => 60, // 3 galões
            ],
            [
                'nome' => 'Frotador',
                'descricao' => 'Produto para limpeza e higienização de estofados. Galão de 20L.',
                'preco_custo' => 180.00,
                'preco_venda' => 280.00,
                'unidade' => 'L',
                'estoque_inicial' => 40, // 2 galões
            ],
        ];

        foreach ($produtos as $item) {
            $estoqueInicial = $item['estoque_inicial'];
            unset($item['estoque_inicial']);

            $produto = Produto::firstOrCreate(
                ['nome' => $item['nome']],
                $item
            );

            // Criar movimentação de entrada inicial se produto novo
            if ($produto->wasRecentlyCreated && $estoqueInicial > 0) {
                Estoque::create([
                    'produto_id' => $produto->id,
                    'tipo' => 'entrada',
                    'quantidade' => $estoqueInicial,
                    'motivo' => 'Estoque inicial',
                    'criado_por' => 'Sistema',
                    'data_movimento' => now(),
                ]);
            }
        }
    }
}
