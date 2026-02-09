<?php

namespace Database\Seeders;

use App\Models\Cadastro;
use App\Models\Categoria;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Produto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuickTestDataSeeder extends Seeder
{
    /**
     * Seeder rápido para criar dados básicos de teste
     */
    public function run(): void
    {
        $this->command->info('🚀 Criando dados básicos de teste...');

        DB::beginTransaction();

        try {
            // 1. Criar categorias básicas
            $categorias = $this->createCategorias();

            // 2. Criar alguns produtos
            $produtos = $this->createProdutos();

            // 3. Criar alguns cadastros
            $cadastros = $this->createCadastros();

            // 4. Criar alguns orçamentos
            $orcamentos = $this->createOrcamentos($cadastros, $produtos);

            // 5. Criar algumas movimentações financeiras
            $this->createFinanceiros($categorias, $cadastros);

            DB::commit();

            $this->command->info('✅ Dados básicos criados com sucesso!');
            $this->showStatistics();

        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('❌ Erro: '.$e->getMessage());
            throw $e;
        }
    }

    private function createCategorias(): array
    {
        $this->command->info('📁 Criando categorias...');

        $categorias = [];
        $categoriasData = [
            ['nome' => 'Vendas', 'tipo' => 'receita', 'cor' => '#10B981'],
            ['nome' => 'Serviços', 'tipo' => 'receita', 'cor' => '#3B82F6'],
            ['nome' => 'Materiais', 'tipo' => 'despesa', 'cor' => '#EF4444'],
            ['nome' => 'Salários', 'tipo' => 'despesa', 'cor' => '#F59E0B'],
        ];

        foreach ($categoriasData as $categoria) {
            $categorias[] = Categoria::firstOrCreate(
                ['nome' => $categoria['nome']],
                $categoria
            );
        }

        return $categorias;
    }

    private function createProdutos(): array
    {
        $this->command->info('📦 Criando produtos...');

        $produtos = [];
        $produtosData = [
            ['nome' => 'Granito Branco', 'categoria' => 'Granitos', 'preco' => 250.00],
            ['nome' => 'Mármore Carrara', 'categoria' => 'Mármores', 'preco' => 450.00],
            ['nome' => 'Quartzo Branco', 'categoria' => 'Quartzo', 'preco' => 380.00],
            ['nome' => 'Instalação', 'categoria' => 'Serviços', 'preco' => 150.00],
        ];

        foreach ($produtosData as $produto) {
            $produtos[] = Produto::create([
                'nome' => $produto['nome'],
                'categoria' => $produto['categoria'],
                'preco' => $produto['preco'],
                'unidade' => 'm²',
                'ativo' => true,
            ]);
        }

        return $produtos;
    }

    private function createCadastros(): array
    {
        $this->command->info('👥 Criando cadastros...');

        $cadastros = [];

        // Clientes
        for ($i = 1; $i <= 10; $i++) {
            $cadastros[] = Cadastro::create([
                'nome' => fake('pt_BR')->name,
                'tipo' => 'cliente',
                'documento' => fake('pt_BR')->cpf(false),
                'email' => fake('pt_BR')->unique()->safeEmail,
                'telefone' => fake('pt_BR')->cellphone(false),
                'cep' => fake('pt_BR')->postcode,
                'logradouro' => fake('pt_BR')->streetName,
                'numero' => fake('pt_BR')->buildingNumber,
                'bairro' => fake('pt_BR')->cityDistrict,
                'cidade' => fake('pt_BR')->city,
                'estado' => fake('pt_BR')->stateAbbr,
            ]);
        }

        // Vendedores
        for ($i = 1; $i <= 3; $i++) {
            $cadastros[] = Cadastro::create([
                'nome' => fake('pt_BR')->name,
                'tipo' => 'vendedor',
                'documento' => fake('pt_BR')->cpf(false),
                'email' => fake('pt_BR')->unique()->safeEmail,
                'telefone' => fake('pt_BR')->cellphone(false),
                'comissao_percentual' => fake()->randomFloat(2, 5, 10),
            ]);
        }

        return $cadastros;
    }

    private function createOrcamentos(array $cadastros, array $produtos): array
    {
        $this->command->info('💰 Criando orçamentos...');

        $orcamentos = [];
        $clientes = array_filter($cadastros, fn ($c) => $c->tipo === 'cliente');

        for ($i = 1; $i <= 8; $i++) {
            $cliente = fake()->randomElement($clientes);

            $orcamento = Orcamento::create([
                'numero' => 'ORC-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'cadastro_id' => $cliente->id,
                'data_orcamento' => fake()->dateTimeBetween('-2 months', 'now'),
                'data_validade' => fake()->dateTimeBetween('now', '+1 month'),
                'status' => fake()->randomElement(['rascunho', 'enviado', 'aprovado']),
                'valor_total' => 0,
            ]);

            // Adicionar itens
            $valorTotal = 0;
            for ($j = 1; $j <= fake()->numberBetween(1, 3); $j++) {
                $produto = fake()->randomElement($produtos);
                $quantidade = fake()->randomFloat(2, 1, 10);
                $valorItem = $quantidade * $produto->preco;
                $valorTotal += $valorItem;

                OrcamentoItem::create([
                    'orcamento_id' => $orcamento->id,
                    'produto_id' => $produto->id,
                    'descricao' => $produto->nome,
                    'quantidade' => $quantidade,
                    'valor_unitario' => $produto->preco,
                    'valor_total' => $valorItem,
                ]);
            }

            $orcamento->update(['valor_total' => $valorTotal]);
            $orcamentos[] = $orcamento;
        }

        return $orcamentos;
    }

    private function createFinanceiros(array $categorias, array $cadastros): void
    {
        $this->command->info('💳 Criando financeiros...');

        $categoriasMap = collect($categorias)->keyBy('nome');

        // Receitas
        for ($i = 1; $i <= 10; $i++) {
            $cliente = fake()->randomElement(array_filter($cadastros, fn ($c) => $c->tipo === 'cliente'));
            $valor = fake()->randomFloat(2, 500, 5000);
            $status = fake()->randomElement(['pago', 'pendente']);

            Financeiro::create([
                'cadastro_id' => $cliente->id,
                'tipo' => 'entrada',
                'categoria' => 'Vendas',
                'categoria_id' => $categoriasMap->get('Vendas')?->id,
                'descricao' => 'Venda - '.$cliente->nome,
                'valor' => $valor,
                'valor_pago' => $status === 'pago' ? $valor : 0,
                'data' => fake()->dateTimeBetween('-2 months', 'now'),
                'data_vencimento' => fake()->dateTimeBetween('-1 month', '+1 month'),
                'status' => $status,
                'forma_pagamento' => fake()->randomElement(['pix', 'dinheiro', 'cartao']),
            ]);
        }

        // Despesas
        for ($i = 1; $i <= 10; $i++) {
            $valor = fake()->randomFloat(2, 100, 2000);
            $status = fake()->randomElement(['pago', 'pendente']);

            Financeiro::create([
                'tipo' => 'saida',
                'categoria' => fake()->randomElement(['Materiais', 'Salários']),
                'categoria_id' => fake()->randomElement([$categoriasMap->get('Materiais')?->id, $categoriasMap->get('Salários')?->id]),
                'descricao' => fake('pt_BR')->sentence,
                'valor' => $valor,
                'valor_pago' => $status === 'pago' ? $valor : 0,
                'data' => fake()->dateTimeBetween('-2 months', 'now'),
                'data_vencimento' => fake()->dateTimeBetween('-1 month', '+1 month'),
                'status' => $status,
                'forma_pagamento' => fake()->randomElement(['transferencia', 'pix']),
            ]);
        }
    }

    private function showStatistics(): void
    {
        $this->command->table(['Entidade', 'Total'], [
            ['Categorias', Categoria::count()],
            ['Produtos', Produto::count()],
            ['Cadastros', Cadastro::count()],
            ['Orçamentos', Orcamento::count()],
            ['Itens Orçamento', OrcamentoItem::count()],
            ['Financeiros', Financeiro::count()],
        ]);
    }
}
