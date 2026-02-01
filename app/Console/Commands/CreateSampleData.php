<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\Cadastro;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Financeiro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateSampleData extends Command
{
    protected $signature = 'app:create-sample-data';
    protected $description = 'Criar dados de exemplo para demonstração';

    public function handle()
    {
        $this->info('🚀 Criando dados de exemplo...');

        try {
            DB::beginTransaction();

            // 1. Categorias
            $this->createCategorias();
            
            // 2. Produtos  
            $this->createProdutos();
            
            // 3. Usar cadastros existentes e criar alguns orçamentos
            $this->createOrcamentos();
            
            // 4. Financeiros
            $this->createFinanceiros();

            DB::commit();
            
            $this->info('✅ Dados de exemplo criados com sucesso!');
            $this->showStats();
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error('❌ Erro: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function createCategorias()
    {
        $this->line('📁 Criando categorias...');
        
        $categorias = [
            ['nome' => 'Vendas de Produtos', 'tipo' => 'receita', 'cor' => '#10B981'],
            ['nome' => 'Serviços de Instalação', 'tipo' => 'receita', 'cor' => '#3B82F6'],
            ['nome' => 'Compra de Materiais', 'tipo' => 'despesa', 'cor' => '#EF4444'],
            ['nome' => 'Despesas Operacionais', 'tipo' => 'despesa', 'cor' => '#F59E0B'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                ['nome' => $categoria['nome']], 
                $categoria
            );
        }
    }

    private function createProdutos()
    {
        if (Produto::count() > 0) {
            $this->line('📦 Produtos já existem, pulando...');
            return;
        }

        $this->line('📦 Criando produtos...');

        $produtos = [
            ['nome' => 'Granito Branco Ceará', 'preco_venda' => 280.00, 'preco_custo' => 200.00, 'unidade' => 'm²'],
            ['nome' => 'Granito Preto São Gabriel', 'preco_venda' => 320.00, 'preco_custo' => 230.00, 'unidade' => 'm²'],
            ['nome' => 'Mármore Branco Carrara', 'preco_venda' => 450.00, 'preco_custo' => 350.00, 'unidade' => 'm²'],
            ['nome' => 'Mármore Travertino', 'preco_venda' => 380.00, 'preco_custo' => 280.00, 'unidade' => 'm²'],
            ['nome' => 'Quartzo Branco Absoluto', 'preco_venda' => 520.00, 'preco_custo' => 400.00, 'unidade' => 'm²'],
            ['nome' => 'Quartzo Cinza Platinum', 'preco_venda' => 480.00, 'preco_custo' => 380.00, 'unidade' => 'm²'],
            ['nome' => 'Instalação de Bancada', 'preco_venda' => 150.00, 'preco_custo' => 80.00, 'unidade' => 'serv'],
            ['nome' => 'Medição e Template', 'preco_venda' => 80.00, 'preco_custo' => 40.00, 'unidade' => 'serv'],
        ];

        foreach ($produtos as $produto) {
            Produto::create($produto);
        }
    }

    private function createOrcamentos()
    {
        if (Orcamento::count() > 0) {
            $this->line('💰 Orçamentos já existem, pulando...');
            return;
        }

        $this->line('💰 Criando orçamentos...');

        $clientes = Cadastro::where('tipo', 'cliente')->limit(20)->get();
        $produtos = Produto::all();
        
        if ($clientes->isEmpty()) {
            $this->warn('⚠️ Não há clientes cadastrados. Criando alguns...');
            $this->createSampleClientes();
            $clientes = Cadastro::where('tipo', 'cliente')->limit(20)->get();
        }

        for ($i = 1; $i <= 12; $i++) {
            $cliente = $clientes->random();
            
            $orcamento = Orcamento::create([
                'numero' => 'ORC-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'cadastro_id' => $cliente->id,
                'data_orcamento' => now()->subDays(rand(1, 60)),
                'data_validade' => now()->addDays(rand(15, 45)),
                'status' => collect(['rascunho', 'enviado', 'aprovado', 'rejeitado'])->random(),
                'valor_total' => 0,
                'observacoes' => 'Orçamento de exemplo para demonstração',
            ]);

            // Adicionar itens
            $valorTotal = 0;
            $numItens = rand(1, 4);
            
            for ($j = 1; $j <= $numItens; $j++) {
                $produto = $produtos->random();
                $quantidade = round(rand(100, 2000) / 100, 2); // 1.00 a 20.00
                $valorUnitario = $produto->preco_venda * (rand(80, 120) / 100); // Variação de ±20%
                $valorItem = $quantidade * $valorUnitario;
                $valorTotal += $valorItem;
                
                OrcamentoItem::create([
                    'orcamento_id' => $orcamento->id,
                    'produto_id' => $produto->id,
                    'descricao' => $produto->nome,
                    'quantidade' => $quantidade,
                    'valor_unitario' => $valorUnitario,
                    'valor_total' => $valorItem,
                ]);
            }
            
            $orcamento->update(['valor_total' => $valorTotal]);
        }
    }

    private function createSampleClientes()
    {
        $clientes = [
            ['nome' => 'João Silva', 'documento' => '123.456.789-01', 'email' => 'joao@email.com'],
            ['nome' => 'Maria Santos', 'documento' => '987.654.321-02', 'email' => 'maria@email.com'],
            ['nome' => 'Carlos Oliveira', 'documento' => '456.789.123-03', 'email' => 'carlos@email.com'],
            ['nome' => 'Ana Costa', 'documento' => '321.654.987-04', 'email' => 'ana@email.com'],
            ['nome' => 'Pedro Almeida', 'documento' => '789.123.456-05', 'email' => 'pedro@email.com'],
        ];

        foreach ($clientes as $cliente) {
            Cadastro::create([
                'nome' => $cliente['nome'],
                'tipo' => 'cliente',
                'documento' => $cliente['documento'],
                'email' => $cliente['email'],
                'telefone' => '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'cep' => rand(10000, 99999) . '-' . rand(100, 999),
                'logradouro' => 'Rua Exemplo, ' . rand(100, 999),
                'numero' => rand(1, 999),
                'bairro' => 'Centro',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
            ]);
        }
    }

    private function createFinanceiros()
    {
        if (Financeiro::count() > 0) {
            $this->line('💳 Financeiros já existem, pulando...');
            return;
        }

        $this->line('💳 Criando movimentações financeiras...');

        $categorias = Categoria::all()->keyBy('nome');
        $clientes = Cadastro::where('tipo', 'cliente')->limit(15)->get();
        $orcamentos = Orcamento::where('status', 'aprovado')->get();

        // Receitas dos orçamentos aprovados
        foreach ($orcamentos as $orcamento) {
            $numParcelas = rand(1, 3);
            $valorParcela = $orcamento->valor_total / $numParcelas;
            
            for ($p = 1; $p <= $numParcelas; $p++) {
                $dataVencimento = now()->addDays(rand(1, 90));
                $status = $dataVencimento < now() ? 'pago' : 'pendente';
                
                Financeiro::create([
                    'cadastro_id' => $orcamento->cadastro_id,
                    'orcamento_id' => $orcamento->id,
                    'tipo' => 'entrada',
                    'categoria' => 'Vendas de Produtos',
                    'categoria_id' => $categorias->get('Vendas de Produtos')?->id,
                    'descricao' => "Pagamento {$orcamento->numero} - Parcela {$p}/{$numParcelas}",
                    'valor' => $valorParcela,
                    'valor_pago' => $status === 'pago' ? $valorParcela : 0,
                    'data' => $orcamento->data_orcamento,
                    'data_vencimento' => $dataVencimento,
                    'data_pagamento' => $status === 'pago' ? $dataVencimento : null,
                    'status' => $status,
                    'forma_pagamento' => collect(['pix', 'dinheiro', 'cartao_credito', 'transferencia'])->random(),
                ]);
            }
        }

        // Receitas avulsas
        foreach ($clientes->take(8) as $cliente) {
            $valor = rand(50000, 300000) / 100; // R$ 500 a R$ 3000
            $status = collect(['pago', 'pendente'])->random();
            
            Financeiro::create([
                'cadastro_id' => $cliente->id,
                'tipo' => 'entrada',
                'categoria' => 'Serviços de Instalação',
                'categoria_id' => $categorias->get('Serviços de Instalação')?->id,
                'descricao' => 'Serviço de instalação - ' . $cliente->nome,
                'valor' => $valor,
                'valor_pago' => $status === 'pago' ? $valor : 0,
                'data' => now()->subDays(rand(1, 30)),
                'data_vencimento' => now()->addDays(rand(1, 30)),
                'status' => $status,
                'forma_pagamento' => collect(['pix', 'dinheiro', 'cartao_debito'])->random(),
            ]);
        }

        // Despesas
        $despesas = [
            ['desc' => 'Compra de granito branco', 'valor' => [100000, 500000], 'cat' => 'Compra de Materiais'],
            ['desc' => 'Compra de mármore', 'valor' => [150000, 600000], 'cat' => 'Compra de Materiais'],
            ['desc' => 'Manutenção de equipamentos', 'valor' => [50000, 200000], 'cat' => 'Despesas Operacionais'],
            ['desc' => 'Combustível para entregas', 'valor' => [30000, 150000], 'cat' => 'Despesas Operacionais'],
            ['desc' => 'Material de escritório', 'valor' => [10000, 80000], 'cat' => 'Despesas Operacionais'],
        ];

        foreach ($despesas as $despesa) {
            for ($i = 1; $i <= rand(1, 3); $i++) {
                $valor = rand($despesa['valor'][0], $despesa['valor'][1]) / 100;
                $status = collect(['pago', 'pendente'])->random();
                
                Financeiro::create([
                    'tipo' => 'saida',
                    'categoria' => $despesa['cat'],
                    'categoria_id' => $categorias->get($despesa['cat'])?->id,
                    'descricao' => $despesa['desc'],
                    'valor' => $valor,
                    'valor_pago' => $status === 'pago' ? $valor : 0,
                    'data' => now()->subDays(rand(1, 60)),
                    'data_vencimento' => now()->addDays(rand(-15, 30)),
                    'status' => $status,
                    'forma_pagamento' => collect(['transferencia', 'pix', 'boleto'])->random(),
                ]);
            }
        }
    }

    private function showStats()
    {
        $this->newLine();
        $this->info('📊 Resumo dos dados criados:');
        $this->table(['Entidade', 'Total'], [
            ['Categorias', Categoria::count()],
            ['Produtos', Produto::count()],
            ['Cadastros', Cadastro::count()],
            ['Orçamentos', Orcamento::count()],
            ['Itens de Orçamento', OrcamentoItem::count()],
            ['Movimentações Financeiras', Financeiro::count()],
        ]);
        
        $this->newLine();
        $this->info('✅ Sistema populado e pronto para demonstração!');
        $this->line('🌐 Acesse: http://localhost/admin');
        $this->line('👤 Login: admin@stofgard.com / admin123');
    }
}
