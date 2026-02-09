<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Cadastro;
use App\Models\Categoria;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Database\Seeder;

class SimpleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Populando dados essenciais...');

        // 1. Criar categorias se não existirem
        $this->createCategorias();

        // 2. Criar produtos se não existirem
        $this->createProdutos();

        // 3. Criar orçamentos com dados existentes
        $this->createOrcamentos();

        // 4. Criar financeiros
        $this->createFinanceiros();

        // 5. Criar ordens de serviço
        $this->createOrdensServico();

        // 6. Criar agendamentos
        $this->createAgendas();

        $this->showStats();
    }

    private function createCategorias(): void
    {
        if (Categoria::count() < 5) {
            $categorias = [
                ['nome' => 'Vendas de Produtos', 'tipo' => 'receita', 'cor' => '#10B981'],
                ['nome' => 'Serviços', 'tipo' => 'receita', 'cor' => '#3B82F6'],
                ['nome' => 'Compra Materiais', 'tipo' => 'despesa', 'cor' => '#EF4444'],
                ['nome' => 'Despesas Gerais', 'tipo' => 'despesa', 'cor' => '#F59E0B'],
            ];

            foreach ($categorias as $cat) {
                Categoria::firstOrCreate(['nome' => $cat['nome']], $cat);
            }
        }
    }

    private function createProdutos(): void
    {
        if (Produto::count() == 0) {
            $produtos = [
                ['nome' => 'Granito Branco Ceará', 'categoria' => 'Granitos', 'preco' => 280.00, 'unidade' => 'm²'],
                ['nome' => 'Mármore Carrara', 'categoria' => 'Mármores', 'preco' => 450.00, 'unidade' => 'm²'],
                ['nome' => 'Quartzo Branco', 'categoria' => 'Quartzo', 'preco' => 380.00, 'unidade' => 'm²'],
                ['nome' => 'Instalação Bancada', 'categoria' => 'Serviços', 'preco' => 150.00, 'unidade' => 'serv'],
                ['nome' => 'Medição e Template', 'categoria' => 'Serviços', 'preco' => 80.00, 'unidade' => 'serv'],
            ];

            foreach ($produtos as $prod) {
                Produto::create($prod + ['ativo' => true]);
            }
        }
    }

    private function createOrcamentos(): void
    {
        if (Orcamento::count() == 0) {
            $clientes = Cadastro::where('tipo', 'cliente')->limit(20)->get();
            $produtos = Produto::all();

            if ($clientes->isEmpty() || $produtos->isEmpty()) {
                return;
            }

            for ($i = 1; $i <= 15; $i++) {
                $cliente = $clientes->random();

                $orcamento = Orcamento::create([
                    'numero' => 'ORC-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                    'cadastro_id' => $cliente->id,
                    'data_orcamento' => now()->subDays(rand(1, 90)),
                    'data_validade' => now()->addDays(rand(15, 45)),
                    'status' => collect(['rascunho', 'enviado', 'aprovado', 'rejeitado'])->random(),
                    'valor_total' => 0,
                ]);

                // Adicionar itens
                $valorTotal = 0;
                for ($j = 1; $j <= rand(1, 4); $j++) {
                    $produto = $produtos->random();
                    $quantidade = round(rand(100, 2000) / 100, 2); // 1.00 a 20.00
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
            }
        }
    }

    private function createFinanceiros(): void
    {
        if (Financeiro::count() == 0) {
            $categorias = Categoria::all()->keyBy('nome');
            $clientes = Cadastro::where('tipo', 'cliente')->limit(15)->get();

            // Receitas
            foreach ($clientes as $cliente) {
                if (rand(1, 100) <= 70) { // 70% chance
                    $valor = rand(50000, 500000) / 100; // R$ 500 a R$ 5000
                    $status = collect(['pago', 'pendente', 'atrasado'])->random();

                    Financeiro::create([
                        'cadastro_id' => $cliente->id,
                        'tipo' => 'entrada',
                        'categoria' => 'Vendas de Produtos',
                        'categoria_id' => $categorias->get('Vendas de Produtos')?->id,
                        'descricao' => 'Venda para '.$cliente->nome,
                        'valor' => $valor,
                        'valor_pago' => $status === 'pago' ? $valor : 0,
                        'data' => now()->subDays(rand(1, 60)),
                        'data_vencimento' => now()->addDays(rand(-30, 30)),
                        'status' => $status,
                        'forma_pagamento' => collect(['pix', 'dinheiro', 'cartao_credito', 'transferencia'])->random(),
                    ]);
                }
            }

            // Despesas
            for ($i = 1; $i <= 20; $i++) {
                $valor = rand(10000, 200000) / 100; // R$ 100 a R$ 2000
                $status = collect(['pago', 'pendente'])->random();

                Financeiro::create([
                    'tipo' => 'saida',
                    'categoria' => collect(['Compra Materiais', 'Despesas Gerais'])->random(),
                    'categoria_id' => collect([$categorias->get('Compra Materiais')?->id, $categorias->get('Despesas Gerais')?->id])->random(),
                    'descricao' => collect([
                        'Compra de matéria-prima',
                        'Despesa de transporte',
                        'Manutenção equipamento',
                        'Material de escritório',
                    ])->random(),
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

    private function createOrdensServico(): void
    {
        if (OrdemServico::count() == 0) {
            $orcamentos = Orcamento::where('status', 'aprovado')->get();
            $clientes = Cadastro::where('tipo', 'cliente')->limit(10)->get();

            foreach ($orcamentos as $orcamento) {
                OrdemServico::create([
                    'numero' => 'OS-'.str_pad(OrdemServico::count() + 1, 4, '0', STR_PAD_LEFT),
                    'cadastro_id' => $orcamento->cadastro_id,
                    'orcamento_id' => $orcamento->id,
                    'data_abertura' => now()->subDays(rand(1, 30)),
                    'data_prevista' => now()->addDays(rand(5, 20)),
                    'status' => collect(['aberta', 'em_andamento', 'concluida'])->random(),
                    'valor_total' => $orcamento->valor_total,
                    'descricao' => 'OS ref. orçamento '.$orcamento->numero,
                ]);
            }

            // OSs independentes
            foreach ($clientes->take(5) as $cliente) {
                OrdemServico::create([
                    'numero' => 'OS-'.str_pad(OrdemServico::count() + 1, 4, '0', STR_PAD_LEFT),
                    'cadastro_id' => $cliente->id,
                    'data_abertura' => now()->subDays(rand(1, 20)),
                    'data_prevista' => now()->addDays(rand(3, 15)),
                    'status' => collect(['aberta', 'em_andamento', 'concluida'])->random(),
                    'valor_total' => rand(50000, 300000) / 100,
                    'descricao' => 'Serviço para '.$cliente->nome,
                ]);
            }
        }
    }

    private function createAgendas(): void
    {
        if (Agenda::count() == 0) {
            $clientes = Cadastro::where('tipo', 'cliente')->limit(15)->get();
            $ordensServico = OrdemServico::all();

            // Agendamentos de medição
            foreach ($clientes->take(10) as $cliente) {
                $dataAgenda = now()->addDays(rand(1, 30));

                Agenda::create([
                    'cadastro_id' => $cliente->id,
                    'titulo' => 'Medição - '.$cliente->nome,
                    'descricao' => 'Agendamento para medição no local',
                    'data_inicio' => $dataAgenda,
                    'data_fim' => $dataAgenda->copy()->addHours(2),
                    'tipo' => 'medicao',
                    'status' => 'agendado',
                    'endereco_completo' => $cliente->logradouro.', '.$cliente->numero,
                ]);
            }

            // Agendamentos de instalação
            foreach ($ordensServico->take(8) as $os) {
                $dataAgenda = now()->addDays(rand(2, 25));

                Agenda::create([
                    'cadastro_id' => $os->cadastro_id,
                    'ordem_servico_id' => $os->id,
                    'titulo' => 'Instalação - OS '.$os->numero,
                    'descricao' => 'Instalação conforme OS '.$os->numero,
                    'data_inicio' => $dataAgenda,
                    'data_fim' => $dataAgenda->copy()->addHours(4),
                    'tipo' => 'instalacao',
                    'status' => $os->status === 'concluida' ? 'concluido' : 'agendado',
                    'endereco_completo' => $os->cadastro->logradouro.', '.$os->cadastro->numero,
                ]);
            }
        }
    }

    private function showStats(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Dados populados:');
        $this->command->table(['Entidade', 'Total'], [
            ['Categorias', Categoria::count()],
            ['Produtos', Produto::count()],
            ['Cadastros', Cadastro::count()],
            ['Orçamentos', Orcamento::count()],
            ['Itens Orçamento', OrcamentoItem::count()],
            ['Ordens Serviço', OrdemServico::count()],
            ['Agendamentos', Agenda::count()],
            ['Financeiros', Financeiro::count()],
        ]);

        $this->command->newLine();
        $this->command->info('✅ Sistema populado e pronto para uso!');
        $this->command->line('🌐 Acesse: http://localhost/admin');
        $this->command->line('👤 Login: admin@stofgard.com / admin123');
    }
}
