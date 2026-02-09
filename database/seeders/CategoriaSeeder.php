<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            // Receitas
            [
                'nome' => 'Vendas de Produtos',
                'tipo' => 'financeiro_receita',
                'cor' => '#10b981', // green-500
                'icone' => '🛒',
            ],
            [
                'nome' => 'Serviços Prestados',
                'tipo' => 'financeiro_receita',
                'cor' => '#3b82f6', // blue-500
                'icone' => '🔧',
            ],
            [
                'nome' => 'Outras Receitas',
                'tipo' => 'financeiro_receita',
                'cor' => '#6366f1', // indigo-500
                'icone' => '💰',
            ],

            // Despesas
            [
                'nome' => 'Fornecedores',
                'tipo' => 'financeiro_despesa',
                'cor' => '#ef4444', // red-500
                'icone' => '🚚',
            ],
            [
                'nome' => 'Pessoal e Salários',
                'tipo' => 'financeiro_despesa',
                'cor' => '#f97316', // orange-500
                'icone' => '👥',
            ],
            [
                'nome' => 'Aluguel e Condomínio',
                'tipo' => 'financeiro_despesa',
                'cor' => '#eab308', // yellow-500
                'icone' => '🏢',
            ],
            [
                'nome' => 'Energia e Água',
                'tipo' => 'financeiro_despesa',
                'cor' => '#0ea5e9', // sky-500
                'icone' => '💡',
            ],
            [
                'nome' => 'Marketing',
                'tipo' => 'financeiro_despesa',
                'cor' => '#ec4899', // pink-500
                'icone' => '📢',
            ],
            [
                'nome' => 'Impostos e Taxas',
                'tipo' => 'financeiro_despesa',
                'cor' => '#64748b', // slate-500
                'icone' => '🏛️',
            ],
            [
                'nome' => 'Manutenção',
                'tipo' => 'financeiro_despesa',
                'cor' => '#8b5cf6', // violet-500
                'icone' => '🛠️',
            ],
        ];

        foreach ($categorias as $cat) {
            Categoria::firstOrCreate(
                ['nome' => $cat['nome'], 'tipo' => $cat['tipo']],
                $cat
            );
        }
    }
}
