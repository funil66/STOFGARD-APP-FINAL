<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriasSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Categorias Financeiras - Receitas
        $categoriasReceita = [
            ['nome' => 'Serviço', 'icone' => '🧹', 'cor' => '#10b981', 'ordem' => 1],
            ['nome' => 'Produto', 'icone' => '📦', 'cor' => '#3b82f6', 'ordem' => 2],
        ];

        // Categorias Financeiras - Despesas
        $categoriasDespesa = [
            ['nome' => 'Produto', 'icone' => '📦', 'cor' => '#ef4444', 'ordem' => 1],
            ['nome' => 'Alimentação', 'icone' => '🍽️', 'cor' => '#f59e0b', 'ordem' => 2],
            ['nome' => 'Combustível', 'icone' => '⛽', 'cor' => '#8b5cf6', 'ordem' => 3],
            ['nome' => 'Comissão', 'icone' => '💰', 'cor' => '#ec4899', 'ordem' => 4],
            ['nome' => 'Salário', 'icone' => '💵', 'cor' => '#06b6d4', 'ordem' => 5],
            ['nome' => 'Fornecedor', 'icone' => '🏭', 'cor' => '#6366f1', 'ordem' => 6],
            ['nome' => 'Aluguel', 'icone' => '🏠', 'cor' => '#f97316', 'ordem' => 7],
            ['nome' => 'Energia', 'icone' => '⚡', 'cor' => '#eab308', 'ordem' => 8],
            ['nome' => 'Água', 'icone' => '💧', 'cor' => '#0ea5e9', 'ordem' => 9],
            ['nome' => 'Internet', 'icone' => '🌐', 'cor' => '#8b5cf6', 'ordem' => 10],
            ['nome' => 'Telefone', 'icone' => '📱', 'cor' => '#14b8a6', 'ordem' => 11],
            ['nome' => 'Manutenção', 'icone' => '🔧', 'cor' => '#64748b', 'ordem' => 12],
            ['nome' => 'Marketing', 'icone' => '📢', 'cor' => '#ec4899', 'ordem' => 13],
            ['nome' => 'Impostos', 'icone' => '📊', 'cor' => '#dc2626', 'ordem' => 14],
            ['nome' => 'Equipamentos', 'icone' => '🛠️', 'cor' => '#475569', 'ordem' => 15],
            ['nome' => 'Material', 'icone' => '📋', 'cor' => '#78716c', 'ordem' => 16],
            ['nome' => 'Outros', 'icone' => '📌', 'cor' => '#94a3b8', 'ordem' => 17],
        ];

        // Inserir categorias de receita
        foreach ($categoriasReceita as $cat) {
            Categoria::create([
                'tipo' => 'financeiro_receita',
                'nome' => $cat['nome'],
                'slug' => \Illuminate\Support\Str::slug('receita-'.$cat['nome']),
                'icone' => $cat['icone'],
                'cor' => $cat['cor'],
                'ordem' => $cat['ordem'],
                'ativo' => true,
            ]);
        }

        // Inserir categorias de despesa
        foreach ($categoriasDespesa as $cat) {
            Categoria::create([
                'tipo' => 'financeiro_despesa',
                'nome' => $cat['nome'],
                'slug' => \Illuminate\Support\Str::slug('despesa-'.$cat['nome']),
                'icone' => $cat['icone'],
                'cor' => $cat['cor'],
                'ordem' => $cat['ordem'],
                'ativo' => true,
            ]);
        }

        // Categorias de Produtos
        $categoriasProdutos = [
            ['nome' => 'Químico', 'icone' => '🧪', 'cor' => '#3b82f6', 'ordem' => 1],
            ['nome' => 'Equipamento', 'icone' => '🔧', 'cor' => '#64748b', 'ordem' => 2],
            ['nome' => 'Material Consumo', 'icone' => '📦', 'cor' => '#f59e0b', 'ordem' => 3],
        ];

        foreach ($categoriasProdutos as $cat) {
            Categoria::create([
                'tipo' => 'produto',
                'nome' => $cat['nome'],
                'slug' => \Illuminate\Support\Str::slug('produto-'.$cat['nome']),
                'icone' => $cat['icone'],
                'cor' => $cat['cor'],
                'ordem' => $cat['ordem'],
                'ativo' => true,
            ]);
        }

        // Categorias com slug predefinido
        $categorias = [
            ['nome' => 'Venda de Serviço', 'slug' => 'venda-servico', 'tipo' => 'receita', 'sistema' => true],
            ['nome' => 'Venda de Produto', 'slug' => 'venda-produto', 'tipo' => 'receita', 'sistema' => true],
            ['nome' => 'Despesas Gerais',  'slug' => 'despesas-gerais', 'tipo' => 'despesa', 'sistema' => true],
        ];

        foreach ($categorias as $cat) {
            Categoria::updateOrCreate(
                ['slug' => $cat['slug']], // Busca pelo slug
                $cat // Atualiza ou Cria
            );
        }

        // Categorias de Comissão (Sistema) - para separação financeira automática
        $categoriasComissao = [
            [
                'nome' => 'Comissão Vendedor',
                'slug' => 'comissao-vendedor',
                'tipo' => 'financeiro_despesa',
                'icone' => '👤💰',
                'cor' => '#8b5cf6',
                'descricao' => 'Comissão paga ao vendedor pela venda de serviços/produtos',
                'ordem' => 100,
            ],
            [
                'nome' => 'Comissão Loja',
                'slug' => 'comissao-loja',
                'tipo' => 'financeiro_despesa',
                'icone' => '🏪💰',
                'cor' => '#ec4899',
                'descricao' => 'Comissão paga à loja indicadora pela venda de serviços/produtos',
                'ordem' => 101,
            ],
        ];

        foreach ($categoriasComissao as $cat) {
            Categoria::updateOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['ativo' => true])
            );
        }

        $this->command->info('✅ Categorias criadas com sucesso!');
        $this->command->info('📊 Receitas: '.count($categoriasReceita).' categorias');
        $this->command->info('📊 Despesas: '.count($categoriasDespesa).' categorias');
        $this->command->info('📊 Produtos: '.count($categoriasProdutos).' categorias');
    }
}
