<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Cadastro;
use App\Models\Categoria;
use App\Models\Equipamento;
use App\Models\Estoque;
use App\Models\Financeiro;
use App\Models\Garantia;
use App\Models\ListaDesejo;
use App\Models\NotaFiscal;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use App\Models\Produto;
use App\Models\TabelaPreco;
use App\Models\Tarefa;
// use App\Models\TransacaoFinanceira; // Removido - sistema legacy
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompleteTestDataSeeder extends Seeder
{
    /**
     * Seeder completo para popular todas as tabelas com dados realistas
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando população completa de dados de teste...');
        
        // Verificar se estamos em ambiente seguro
        if (!app()->environment(['local', 'testing'])) {
            $this->command->error('❌ Este seeder só pode ser executado em ambiente local ou de teste!');
            return;
        }

        DB::beginTransaction();
        
        try {
            // 1. Limpar dados existentes
            $this->clearExistingData();
            
            // 2. Criar usuários do sistema
            $usuarios = $this->createUsuarios();
            
            // 3. Criar categorias
            $categorias = $this->createCategorias();
            
            // 4. Criar cadastros (clientes, parceiros, etc.)
            $cadastros = $this->createCadastros();
            
            // 5. Criar produtos e tabelas de preço
            $produtos = $this->createProdutos();
            $this->createTabelasPreco($produtos);
            
            // 6. Criar equipamentos
            $equipamentos = $this->createEquipamentos();
            
            // 7. Criar estoques
            $this->createEstoques($produtos);
            
            // 8. Criar lista de desejos
            $this->createListaDesejos($produtos, $cadastros);
            
            // 9. Criar orçamentos
            $orcamentos = $this->createOrcamentos($cadastros, $produtos);
            
            // 10. Criar ordens de serviço
            $ordensServico = $this->createOrdensServico($cadastros, $orcamentos, $produtos);
            
            // 11. Criar agendamentos
            $this->createAgendamentos($cadastros, $ordensServico);
            
            // 12. Criar movimentações financeiras
            $this->createFinanceiros($categorias, $cadastros, $orcamentos, $ordensServico);
            
            // 13. Criar garantias
            $this->createGarantias($cadastros, $produtos, $ordensServico);
            
            // 14. Criar notas fiscais
            $this->createNotasFiscais($orcamentos, $cadastros);
            
            // 16. Criar tarefas
            $this->createTarefas($usuarios, $cadastros, $ordensServico);
            
            DB::commit();
            $this->command->info('✅ Dados de teste criados com sucesso!');
            $this->showStatistics();
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('❌ Erro ao criar dados: ' . $e->getMessage());
            throw $e;
        }
    }

    private function clearExistingData(): void
    {
        $this->command->warn('⚠️ Limpando dados existentes...');
        
        // Ordem importante para evitar violação de foreign keys
        NotaFiscal::truncate();
        Garantia::truncate();
        Tarefa::truncate();
        // TransacaoFinanceira::truncate(); // Removido - sistema legacy
        Financeiro::truncate();
        Agenda::truncate();
        OrdemServicoItem::truncate();
        OrdemServico::truncate();
        OrcamentoItem::truncate();
        Orcamento::truncate();
        ListaDesejo::truncate();
        Estoque::truncate();
        TabelaPreco::truncate();
        Produto::truncate();
        Equipamento::truncate();
        Cadastro::truncate();
        Categoria::where('nome', '!=', 'Padrão')->delete();
        
        // Manter pelo menos 1 usuário admin
        User::where('email', '!=', 'admin@stofgard.com')->delete();
    }

    private function createUsuarios(): array
    {
        $this->command->info('👤 Criando usuários...');
        
        $usuarios = [];
        
        // Admin principal
        $usuarios[] = User::firstOrCreate([
            'email' => 'admin@stofgard.com'
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
        ]);
        
        // Vendedores
        for ($i = 1; $i <= 5; $i++) {
            $usuarios[] = User::create([
                'name' => fake('pt_BR')->name,
                'email' => fake('pt_BR')->unique()->safeEmail,
                'password' => Hash::make('password123'),
                'is_admin' => false,
            ]);
        }
        
        return $usuarios;
    }

    private function createCategorias(): array
    {
        $this->command->info('📁 Criando categorias...');
        
        $categorias = [];
        $categoriasData = [
            // Receitas
            ['nome' => 'Vendas de Produtos', 'tipo' => 'receita', 'cor' => '#10B981'],
            ['nome' => 'Serviços de Instalação', 'tipo' => 'receita', 'cor' => '#3B82F6'],
            ['nome' => 'Serviços de Medição', 'tipo' => 'receita', 'cor' => '#6366F1'],
            ['nome' => 'Comissões de Vendas', 'tipo' => 'receita', 'cor' => '#8B5CF6'],
            ['nome' => 'Receitas Diversas', 'tipo' => 'receita', 'cor' => '#06B6D4'],
            
            // Despesas
            ['nome' => 'Compra de Materiais', 'tipo' => 'despesa', 'cor' => '#EF4444'],
            ['nome' => 'Salários e Encargos', 'tipo' => 'despesa', 'cor' => '#F59E0B'],
            ['nome' => 'Impostos e Taxas', 'tipo' => 'despesa', 'cor' => '#6B7280'],
            ['nome' => 'Marketing e Publicidade', 'tipo' => 'despesa', 'cor' => '#EC4899'],
            ['nome' => 'Combustível e Transporte', 'tipo' => 'despesa', 'cor' => '#F97316'],
            ['nome' => 'Manutenção de Equipamentos', 'tipo' => 'despesa', 'cor' => '#84CC16'],
            ['nome' => 'Aluguel e Utilidades', 'tipo' => 'despesa', 'cor' => '#A855F7'],
            ['nome' => 'Despesas Administrativas', 'tipo' => 'despesa', 'cor' => '#64748B'],
        ];

        foreach ($categoriasData as $categoria) {
            $categorias[] = Categoria::create($categoria);
        }
        
        return $categorias;
    }

    private function createCadastros(): array
    {
        $this->command->info('👥 Criando cadastros...');
        
        $cadastros = [];
        
        // Clientes Pessoa Física
        for ($i = 1; $i <= 25; $i++) {
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
                'observacoes' => fake('pt_BR')->optional(0.3)->sentence,
                'pdf_mostrar_documentos' => fake()->boolean(80),
            ]);
        }
        
        // Clientes Pessoa Jurídica
        for ($i = 1; $i <= 15; $i++) {
            $cadastros[] = Cadastro::create([
                'nome' => fake('pt_BR')->company,
                'tipo' => 'cliente',
                'documento' => fake('pt_BR')->cnpj(false),
                'razao_social' => fake('pt_BR')->company . ' Ltda',
                'email' => fake('pt_BR')->unique()->companyEmail,
                'telefone' => fake('pt_BR')->phoneNumber,
                'cep' => fake('pt_BR')->postcode,
                'logradouro' => fake('pt_BR')->streetName,
                'numero' => fake('pt_BR')->buildingNumber,
                'bairro' => fake('pt_BR')->cityDistrict,
                'cidade' => fake('pt_BR')->city,
                'estado' => fake('pt_BR')->stateAbbr,
                'observacoes' => fake('pt_BR')->optional(0.3)->sentence,
                'pdf_mostrar_documentos' => fake()->boolean(90),
            ]);
        }
        
        // Parceiros - Lojas
        for ($i = 1; $i <= 8; $i++) {
            $cadastros[] = Cadastro::create([
                'nome' => 'Loja ' . fake('pt_BR')->company,
                'tipo' => 'loja',
                'documento' => fake('pt_BR')->cnpj(false),
                'email' => fake('pt_BR')->unique()->companyEmail,
                'telefone' => fake('pt_BR')->phoneNumber,
                'cep' => fake('pt_BR')->postcode,
                'logradouro' => fake('pt_BR')->streetName,
                'numero' => fake('pt_BR')->buildingNumber,
                'bairro' => fake('pt_BR')->cityDistrict,
                'cidade' => fake('pt_BR')->city,
                'estado' => fake('pt_BR')->stateAbbr,
                'comissao_percentual' => fake()->randomFloat(2, 5, 15),
            ]);
        }
        
        // Parceiros - Vendedores
        for ($i = 1; $i <= 12; $i++) {
            $cadastros[] = Cadastro::create([
                'nome' => fake('pt_BR')->name,
                'tipo' => 'vendedor',
                'documento' => fake('pt_BR')->cpf(false),
                'email' => fake('pt_BR')->unique()->safeEmail,
                'telefone' => fake('pt_BR')->cellphone(false),
                'cep' => fake('pt_BR')->postcode,
                'logradouro' => fake('pt_BR')->streetName,
                'numero' => fake('pt_BR')->buildingNumber,
                'bairro' => fake('pt_BR')->cityDistrict,
                'cidade' => fake('pt_BR')->city,
                'estado' => fake('pt_BR')->stateAbbr,
                'comissao_percentual' => fake()->randomFloat(2, 3, 10),
            ]);
        }
        
        // Parceiros - Arquitetos
        for ($i = 1; $i <= 10; $i++) {
            $cadastros[] = Cadastro::create([
                'nome' => 'Arq. ' . fake('pt_BR')->name,
                'tipo' => 'arquiteto',
                'documento' => fake('pt_BR')->cpf(false),
                'email' => fake('pt_BR')->unique()->safeEmail,
                'telefone' => fake('pt_BR')->cellphone(false),
                'cep' => fake('pt_BR')->postcode,
                'logradouro' => fake('pt_BR')->streetName,
                'numero' => fake('pt_BR')->buildingNumber,
                'bairro' => fake('pt_BR')->cityDistrict,
                'cidade' => fake('pt_BR')->city,
                'estado' => fake('pt_BR')->stateAbbr,
                'comissao_percentual' => fake()->randomFloat(2, 5, 12),
                'especialidade' => fake()->randomElement(['Residencial', 'Comercial', 'Industrial', 'Paisagismo']),
            ]);
        }
        
        return $cadastros;
    }

    private function createProdutos(): array
    {
        $this->command->info('📦 Criando produtos...');
        
        $produtos = [];
        
        $categoriasProdutos = [
            'Granitos' => [
                'Granito Branco Ceará',
                'Granito Preto São Gabriel',
                'Granito Verde Ubatuba',
                'Granito Amarelo Ornamental',
                'Granito Cinza Corumbá',
                'Granito Azul Bahia',
                'Granito Rosa Salto',
                'Granito Branco Dallas',
            ],
            'Mármores' => [
                'Mármore Branco Carrara',
                'Mármore Travertino Romano',
                'Mármore Crema Marfil',
                'Mármore Nero Marquina',
                'Mármore Calacatta',
                'Mármore Statuário',
                'Mármore Branco Pighes',
            ],
            'Quartzo' => [
                'Quartzo Branco Absoluto',
                'Quartzo Cinza Platinum',
                'Quartzo Preto Stellar',
                'Quartzo Calacatta Nuvo',
                'Quartzo Carrara Mist',
                'Quartzo Desert Silver',
            ],
            'Serviços' => [
                'Medição e Orçamento',
                'Corte e Furação',
                'Instalação Completa',
                'Acabamento e Polimento',
                'Entrega e Transporte',
                'Manutenção Preventiva',
            ],
        ];

        foreach ($categoriasProdutos as $categoria => $items) {
            foreach ($items as $item) {
                $precoBase = match ($categoria) {
                    'Granitos' => fake()->randomFloat(2, 150, 400),
                    'Mármores' => fake()->randomFloat(2, 200, 600),
                    'Quartzo' => fake()->randomFloat(2, 300, 800),
                    'Serviços' => fake()->randomFloat(2, 50, 200),
                    default => fake()->randomFloat(2, 100, 300),
                };
                
                $produtos[] = Produto::create([
                    'nome' => $item,
                    'categoria' => $categoria,
                    'preco' => $precoBase,
                    'unidade' => $categoria === 'Serviços' ? 'serv' : 'm²',
                    'ativo' => fake()->boolean(95),
                    'descricao' => fake('pt_BR')->optional(0.7)->sentence,
                ]);
            }
        }
        
        return $produtos;
    }

    private function createTabelasPreco(array $produtos): void
    {
        $this->command->info('💰 Criando tabelas de preço...');
        
        foreach ($produtos as $produto) {
            // Preço padrão
            TabelaPreco::create([
                'produto_id' => $produto->id,
                'nome_tabela' => 'Padrão',
                'preco' => $produto->preco,
                'descricao_garantia' => fake('pt_BR')->optional(0.5)->sentence,
            ]);
            
            // Preço promocional (30% dos produtos)
            if (fake()->boolean(30)) {
                TabelaPreco::create([
                    'produto_id' => $produto->id,
                    'nome_tabela' => 'Promocional',
                    'preco' => $produto->preco * 0.85, // 15% de desconto
                    'descricao_garantia' => 'Preço promocional válido até ' . fake()->dateTimeBetween('now', '+3 months')->format('d/m/Y'),
                ]);
            }
            
            // Preço para parceiros (50% dos produtos)
            if (fake()->boolean(50)) {
                TabelaPreco::create([
                    'produto_id' => $produto->id,
                    'nome_tabela' => 'Parceiros',
                    'preco' => $produto->preco * 0.75, // 25% de desconto
                    'descricao_garantia' => 'Preço especial para parceiros cadastrados',
                ]);
            }
        }
    }

    private function createEquipamentos(): array
    {
        $this->command->info('🔧 Criando equipamentos...');
        
        $equipamentos = [];
        
        $equipamentosData = [
            ['nome' => 'Serra Ponte Automática', 'modelo' => 'SP-3200A', 'fabricante' => 'Marmotech'],
            ['nome' => 'Politriz Industrial', 'modelo' => 'PI-1800', 'fabricante' => 'StoneMax'],
            ['nome' => 'Furadeira Diamantada', 'modelo' => 'FD-50', 'fabricante' => 'DiamondTech'],
            ['nome' => 'Bancada de Corte', 'modelo' => 'BC-2000', 'fabricante' => 'Granitos Pro'],
            ['nome' => 'Guindaste Móvel', 'modelo' => 'GM-500', 'fabricante' => 'LiftStone'],
            ['nome' => 'Compressor de Ar', 'modelo' => 'CA-100', 'fabricante' => 'AirTech'],
        ];
        
        foreach ($equipamentosData as $eq) {
            $equipamentos[] = Equipamento::create([
                'nome' => $eq['nome'],
                'modelo' => $eq['modelo'],
                'fabricante' => $eq['fabricante'],
                'numero_serie' => fake()->bothify('####-????-###'),
                'data_aquisicao' => fake()->dateTimeBetween('-5 years', '-1 year'),
                'valor_aquisicao' => fake()->randomFloat(2, 5000, 50000),
                'status' => fake()->randomElement(['ativo', 'manutencao', 'inativo']),
                'observacoes' => fake('pt_BR')->optional(0.4)->sentence,
            ]);
        }
        
        return $equipamentos;
    }

    private function createEstoques(array $produtos): void
    {
        $this->command->info('📊 Criando estoques...');
        
        $produtosFisicos = array_filter($produtos, fn($p) => $p->categoria !== 'Serviços');
        
        foreach ($produtosFisicos as $produto) {
            if (fake()->boolean(80)) { // 80% dos produtos têm estoque
                Estoque::create([
                    'produto_id' => $produto->id,
                    'quantidade' => fake()->randomFloat(2, 0, 100),
                    'quantidade_minima' => fake()->randomFloat(2, 5, 20),
                    'localizacao' => fake()->randomElement(['Galpão A', 'Galpão B', 'Área Externa', 'Showroom']),
                ]);
            }
        }
    }

    private function createListaDesejos(array $produtos, array $cadastros): void
    {
        $this->command->info('💭 Criando lista de desejos...');
        
        $clientes = array_filter($cadastros, fn($c) => $c->tipo === 'cliente');
        
        for ($i = 1; $i <= 30; $i++) {
            $cliente = fake()->randomElement($clientes);
            $produto = fake()->randomElement($produtos);
            
            ListaDesejo::create([
                'cadastro_id' => $cliente->id,
                'produto_id' => $produto->id,
                'quantidade' => fake()->randomFloat(2, 1, 20),
                'prioridade' => fake()->randomElement(['baixa', 'media', 'alta']),
                'observacoes' => fake('pt_BR')->optional(0.6)->sentence,
                'data_prevista_compra' => fake()->optional(0.7)->dateTimeBetween('now', '+6 months'),
            ]);
        }
    }

    private function createOrcamentos(array $cadastros, array $produtos): array
    {
        $this->command->info('💰 Criando orçamentos...');
        
        $orcamentos = [];
        $clientes = array_filter($cadastros, fn($c) => $c->tipo === 'cliente');
        $vendedores = array_filter($cadastros, fn($c) => $c->tipo === 'vendedor');
        $arquitetos = array_filter($cadastros, fn($c) => $c->tipo === 'arquiteto');
        
        for ($i = 1; $i <= 50; $i++) {
            $cliente = fake()->randomElement($clientes);
            $vendedor = fake()->optional(0.7)->randomElement($vendedores);
            $arquiteto = fake()->optional(0.3)->randomElement($arquitetos);
            
            $dataOrcamento = fake()->dateTimeBetween('-6 months', 'now');
            
            $orcamento = Orcamento::create([
                'numero' => 'ORC-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'cadastro_id' => $cliente->id,
                'vendedor_id' => $vendedor?->id,
                'arquiteto_id' => $arquiteto?->id,
                'data_orcamento' => $dataOrcamento,
                'data_validade' => fake()->dateTimeBetween($dataOrcamento, '+3 months'),
                'status' => fake()->randomElement(['rascunho', 'enviado', 'aprovado', 'rejeitado', 'expirado']),
                'desconto_geral' => fake()->optional(0.3)->randomFloat(2, 0, 15),
                'observacoes' => fake('pt_BR')->optional(0.5)->sentence,
                'pdf_mostrar_fotos' => fake()->boolean(60),
            ]);
            
            // Adicionar itens ao orçamento
            $this->createOrcamentoItens($orcamento, $produtos);
            
            $orcamentos[] = $orcamento;
        }
        
        return $orcamentos;
    }

    private function createOrcamentoItens(Orcamento $orcamento, array $produtos): void
    {
        $numItens = fake()->numberBetween(1, 8);
        $valorTotal = 0;
        
        for ($j = 1; $j <= $numItens; $j++) {
            $produto = fake()->randomElement($produtos);
            $quantidade = fake()->randomFloat(2, 1, 25);
            $valorUnitario = $produto->preco * fake()->randomFloat(2, 0.8, 1.3);
            $desconto = fake()->optional(0.3)->randomFloat(2, 0, 10);
            $valorItem = ($quantidade * $valorUnitario) * (1 - ($desconto / 100));
            $valorTotal += $valorItem;
            
            OrcamentoItem::create([
                'orcamento_id' => $orcamento->id,
                'produto_id' => $produto->id,
                'descricao' => $produto->nome,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'desconto_percentual' => $desconto ?? 0,
                'valor_total' => $valorItem,
                'observacoes' => fake('pt_BR')->optional(0.3)->sentence,
            ]);
        }
        
        $orcamento->update(['valor_total' => $valorTotal]);
    }

    private function createOrdensServico(array $cadastros, array $orcamentos, array $produtos): array
    {
        $this->command->info('🔧 Criando ordens de serviço...');
        
        $ordensServico = [];
        $orcamentosAprovados = array_filter($orcamentos, fn($o) => $o->status === 'aprovado');
        
        // Para cada orçamento aprovado, criar uma OS
        foreach ($orcamentosAprovados as $orcamento) {
            $dataAbertura = fake()->dateTimeBetween($orcamento->data_orcamento, 'now');
            
            $os = OrdemServico::create([
                'numero' => 'OS-' . str_pad(count($ordensServico) + 1, 4, '0', STR_PAD_LEFT),
                'cadastro_id' => $orcamento->cadastro_id,
                'orcamento_id' => $orcamento->id,
                'data_abertura' => $dataAbertura,
                'data_prevista' => fake()->dateTimeBetween($dataAbertura, '+2 months'),
                'data_conclusao' => fake()->optional(0.4)->dateTimeBetween($dataAbertura, 'now'),
                'status' => fake()->randomElement(['aberta', 'em_andamento', 'pausada', 'concluida', 'cancelada']),
                'prioridade' => fake()->randomElement(['baixa', 'normal', 'alta', 'urgente']),
                'valor_total' => $orcamento->valor_total,
                'descricao' => fake('pt_BR')->sentence,
                'observacoes' => fake('pt_BR')->optional(0.6)->sentence,
            ]);
            
            // Criar itens da OS baseados nos itens do orçamento
            $this->createOrdemServicoItens($os, $orcamento);
            
            $ordensServico[] = $os;
        }
        
        // Criar algumas OSs independentes (sem orçamento)
        $clientes = array_filter($cadastros, fn($c) => $c->tipo === 'cliente');
        for ($i = 1; $i <= 15; $i++) {
            $cliente = fake()->randomElement($clientes);
            $dataAbertura = fake()->dateTimeBetween('-3 months', 'now');
            
            $os = OrdemServico::create([
                'numero' => 'OS-' . str_pad(count($ordensServico) + 1, 4, '0', STR_PAD_LEFT),
                'cadastro_id' => $cliente->id,
                'data_abertura' => $dataAbertura,
                'data_prevista' => fake()->dateTimeBetween($dataAbertura, '+1 month'),
                'data_conclusao' => fake()->optional(0.6)->dateTimeBetween($dataAbertura, 'now'),
                'status' => fake()->randomElement(['aberta', 'em_andamento', 'concluida']),
                'prioridade' => fake()->randomElement(['normal', 'alta']),
                'valor_total' => fake()->randomFloat(2, 500, 5000),
                'descricao' => fake('pt_BR')->sentence,
                'observacoes' => fake('pt_BR')->optional(0.6)->sentence,
            ]);
            
            // Criar itens simples para OS independente
            $this->createOrdemServicoItensSimples($os, $produtos);
            
            $ordensServico[] = $os;
        }
        
        return $ordensServico;
    }

    private function createOrdemServicoItens(OrdemServico $os, Orcamento $orcamento): void
    {
        foreach ($orcamento->itens as $item) {
            OrdemServicoItem::create([
                'ordem_servico_id' => $os->id,
                'produto_id' => $item->produto_id,
                'descricao' => $item->descricao,
                'quantidade' => $item->quantidade,
                'valor_unitario' => $item->valor_unitario,
                'valor_total' => $item->valor_total,
                'status' => fake()->randomElement(['pendente', 'em_andamento', 'concluido']),
            ]);
        }
    }

    private function createOrdemServicoItensSimples(OrdemServico $os, array $produtos): void
    {
        $numItens = fake()->numberBetween(1, 3);
        $valorTotal = 0;
        
        for ($i = 1; $i <= $numItens; $i++) {
            $produto = fake()->randomElement($produtos);
            $quantidade = fake()->randomFloat(2, 1, 10);
            $valorUnitario = $produto->preco;
            $valorItem = $quantidade * $valorUnitario;
            $valorTotal += $valorItem;
            
            OrdemServicoItem::create([
                'ordem_servico_id' => $os->id,
                'produto_id' => $produto->id,
                'descricao' => $produto->nome,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorItem,
                'status' => fake()->randomElement(['pendente', 'em_andamento', 'concluido']),
            ]);
        }
        
        $os->update(['valor_total' => $valorTotal]);
    }

    private function createAgendamentos(array $cadastros, array $ordensServico): void
    {
        $this->command->info('📅 Criando agendamentos...');
        
        $clientes = array_filter($cadastros, fn($c) => $c->tipo === 'cliente');
        
        // Agendamentos para medições (sem OS)
        for ($i = 1; $i <= 20; $i++) {
            $cliente = fake()->randomElement($clientes);
            $dataAgenda = fake()->dateTimeBetween('now', '+1 month');
            
            Agenda::create([
                'cadastro_id' => $cliente->id,
                'titulo' => 'Medição - ' . $cliente->nome,
                'descricao' => fake('pt_BR')->sentence,
                'data_inicio' => $dataAgenda,
                'data_fim' => $dataAgenda->copy()->addHours(2),
                'tipo' => 'medicao',
                'status' => 'agendado',
                'endereco_completo' => $cliente->logradouro . ', ' . $cliente->numero . ' - ' . $cliente->bairro,
            ]);
        }
        
        // Agendamentos para instalações (com OS)
        foreach ($ordensServico as $os) {
            if (fake()->boolean(60)) { // 60% das OSs têm agendamento
                $dataInstalacao = fake()->dateTimeBetween($os->data_abertura, $os->data_prevista);
                
                Agenda::create([
                    'cadastro_id' => $os->cadastro_id,
                    'ordem_servico_id' => $os->id,
                    'titulo' => 'Instalação - OS ' . $os->numero,
                    'descricao' => 'Instalação conforme OS ' . $os->numero,
                    'data_inicio' => $dataInstalacao,
                    'data_fim' => $dataInstalacao->copy()->addHours(6),
                    'tipo' => 'instalacao',
                    'status' => $os->status === 'concluida' ? 'concluido' : 'agendado',
                    'endereco_completo' => $os->cadastro->logradouro . ', ' . $os->cadastro->numero,
                ]);
            }
        }
    }

    private function createFinanceiros(array $categorias, array $cadastros, array $orcamentos, array $ordensServico): void
    {
        $this->command->info('💳 Criando movimentações financeiras...');
        
        $categoriasMap = collect($categorias)->keyBy('nome');
        
        // Receitas de orçamentos aprovados
        $orcamentosAprovados = array_filter($orcamentos, fn($o) => $o->status === 'aprovado');
        
        foreach ($orcamentosAprovados as $orcamento) {
            $parcelas = fake()->numberBetween(1, 6);
            $valorParcela = $orcamento->valor_total / $parcelas;
            
            for ($p = 1; $p <= $parcelas; $p++) {
                $dataVencimento = fake()->dateTimeBetween($orcamento->data_orcamento, '+4 months');
                $status = $dataVencimento < now() ? 
                    fake()->randomElement(['pago', 'atrasado']) : 'pendente';
                
                Financeiro::create([
                    'cadastro_id' => $orcamento->cadastro_id,
                    'orcamento_id' => $orcamento->id,
                    'tipo' => 'entrada',
                    'categoria' => 'Vendas de Produtos',
                    'categoria_id' => $categoriasMap->get('Vendas de Produtos')?->id,
                    'descricao' => "Pagamento Orçamento {$orcamento->numero} - Parcela {$p}/{$parcelas}",
                    'valor' => $valorParcela,
                    'valor_pago' => $status === 'pago' ? $valorParcela : 0,
                    'data' => $orcamento->data_orcamento,
                    'data_vencimento' => $dataVencimento,
                    'data_pagamento' => $status === 'pago' ? $dataVencimento : null,
                    'status' => $status,
                    'forma_pagamento' => fake()->randomElement(['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'transferencia']),
                    'observacoes' => fake('pt_BR')->optional(0.3)->sentence,
                ]);
            }
        }
        
        // Receitas de serviços (baseadas em OSs)
        foreach ($ordensServico as $os) {
            if (fake()->boolean(40) && !$os->orcamento_id) { // 40% das OSs sem orçamento geram receita de serviço
                $categoria = fake()->randomElement(['Serviços de Instalação', 'Serviços de Medição']);
                
                Financeiro::create([
                    'cadastro_id' => $os->cadastro_id,
                    'ordem_servico_id' => $os->id,
                    'tipo' => 'entrada',
                    'categoria' => $categoria,
                    'categoria_id' => $categoriasMap->get($categoria)?->id,
                    'descricao' => "Serviço - OS {$os->numero}",
                    'valor' => fake()->randomFloat(2, 200, 1000),
                    'valor_pago' => fake()->boolean(70) ? fake()->randomFloat(2, 200, 1000) : 0,
                    'data' => $os->data_abertura,
                    'data_vencimento' => fake()->dateTimeBetween($os->data_abertura, '+1 month'),
                    'status' => fake()->randomElement(['pago', 'pendente']),
                    'forma_pagamento' => fake()->randomElement(['dinheiro', 'pix', 'cartao_debito']),
                ]);
            }
        }
        
        // Comissões para vendedores/arquitetos
        $parceiros = array_filter($cadastros, fn($c) => in_array($c->tipo, ['vendedor', 'arquiteto']));
        
        foreach ($orcamentosAprovados as $orcamento) {
            if ($orcamento->vendedor_id || $orcamento->arquiteto_id) {
                $vendedor = collect($cadastros)->firstWhere('id', $orcamento->vendedor_id);
                $arquiteto = collect($cadastros)->firstWhere('id', $orcamento->arquiteto_id);
                
                if ($vendedor && $vendedor->comissao_percentual) {
                    $valorComissao = $orcamento->valor_total * ($vendedor->comissao_percentual / 100);
                    
                    Financeiro::create([
                        'cadastro_id' => $vendedor->id,
                        'orcamento_id' => $orcamento->id,
                        'tipo' => 'saida',
                        'categoria' => 'Comissões de Vendas',
                        'categoria_id' => $categoriasMap->get('Comissões de Vendas')?->id,
                        'descricao' => "Comissão Vendedor - {$orcamento->numero}",
                        'valor' => $valorComissao,
                        'valor_pago' => fake()->boolean(80) ? $valorComissao : 0,
                        'data' => fake()->dateTimeBetween($orcamento->data_orcamento, 'now'),
                        'data_vencimento' => fake()->dateTimeBetween($orcamento->data_orcamento, '+1 month'),
                        'status' => fake()->randomElement(['pago', 'pendente']),
                        'forma_pagamento' => fake()->randomElement(['transferencia', 'pix']),
                    ]);
                }
                
                if ($arquiteto && $arquiteto->comissao_percentual) {
                    $valorComissao = $orcamento->valor_total * ($arquiteto->comissao_percentual / 100);
                    
                    Financeiro::create([
                        'cadastro_id' => $arquiteto->id,
                        'orcamento_id' => $orcamento->id,
                        'tipo' => 'saida',
                        'categoria' => 'Comissões de Vendas',
                        'categoria_id' => $categoriasMap->get('Comissões de Vendas')?->id,
                        'descricao' => "Comissão Arquiteto - {$orcamento->numero}",
                        'valor' => $valorComissao,
                        'valor_pago' => fake()->boolean(80) ? $valorComissao : 0,
                        'data' => fake()->dateTimeBetween($orcamento->data_orcamento, 'now'),
                        'data_vencimento' => fake()->dateTimeBetween($orcamento->data_orcamento, '+1 month'),
                        'status' => fake()->randomElement(['pago', 'pendente']),
                        'forma_pagamento' => fake()->randomElement(['transferencia', 'pix']),
                    ]);
                }
            }
        }
        
        // Despesas operacionais diversas
        $despesasCategorias = [
            'Compra de Materiais' => 50,
            'Salários e Encargos' => 20,
            'Impostos e Taxas' => 15,
            'Marketing e Publicidade' => 10,
            'Combustível e Transporte' => 25,
            'Manutenção de Equipamentos' => 8,
            'Aluguel e Utilidades' => 12,
            'Despesas Administrativas' => 15,
        ];
        
        foreach ($despesasCategorias as $categoria => $quantidade) {
            for ($i = 1; $i <= $quantidade; $i++) {
                $dataVencimento = fake()->dateTimeBetween('-3 months', '+2 months');
                $status = $dataVencimento < now() ? 
                    fake()->randomElement(['pago', 'atrasado']) : 'pendente';
                
                $valorRange = match ($categoria) {
                    'Salários e Encargos' => [2000, 8000],
                    'Aluguel e Utilidades' => [1500, 5000],
                    'Compra de Materiais' => [500, 10000],
                    'Impostos e Taxas' => [300, 3000],
                    default => [100, 2000],
                };
                
                $valor = fake()->randomFloat(2, $valorRange[0], $valorRange[1]);
                
                Financeiro::create([
                    'tipo' => 'saida',
                    'categoria' => $categoria,
                    'categoria_id' => $categoriasMap->get($categoria)?->id,
                    'descricao' => fake('pt_BR')->sentence,
                    'valor' => $valor,
                    'valor_pago' => $status === 'pago' ? $valor : 0,
                    'data' => fake()->dateTimeBetween('-3 months', 'now'),
                    'data_vencimento' => $dataVencimento,
                    'data_pagamento' => $status === 'pago' ? $dataVencimento : null,
                    'status' => $status,
                    'forma_pagamento' => fake()->randomElement(['transferencia', 'pix', 'boleto', 'cartao_credito']),
                    'observacoes' => fake('pt_BR')->optional(0.4)->sentence,
                ]);
            }
        }
    }

    private function createGarantias(array $cadastros, array $produtos, array $ordensServico): void
    {
        $this->command->info('🛡️ Criando garantias...');
        
        $clientes = array_filter($cadastros, fn($c) => $c->tipo === 'cliente');
        $produtosFisicos = array_filter($produtos, fn($p) => $p->categoria !== 'Serviços');
        
        // Garantias baseadas em OSs concluídas
        $osConcluidas = array_filter($ordensServico, fn($os) => $os->status === 'concluida');
        
        foreach ($osConcluidas as $os) {
            if (fake()->boolean(70)) { // 70% das OSs concluídas têm garantia
                $dataInicio = $os->data_conclusao ?? fake()->dateTimeBetween('-1 year', 'now');
                
                Garantia::create([
                    'cadastro_id' => $os->cadastro_id,
                    'ordem_servico_id' => $os->id,
                    'produto_id' => fake()->randomElement($produtosFisicos)->id,
                    'tipo_garantia' => fake()->randomElement(['instalacao', 'produto', 'servico']),
                    'data_inicio' => $dataInicio,
                    'data_fim' => fake()->dateTimeBetween($dataInicio, $dataInicio->copy()->addYear()),
                    'descricao' => fake('pt_BR')->sentence,
                    'condicoes' => fake('pt_BR')->paragraph,
                    'ativa' => fake()->boolean(90),
                ]);
            }
        }
        
        // Garantias independentes
        for ($i = 1; $i <= 15; $i++) {
            $cliente = fake()->randomElement($clientes);
            $produto = fake()->randomElement($produtosFisicos);
            $dataInicio = fake()->dateTimeBetween('-2 years', 'now');
            
            Garantia::create([
                'cadastro_id' => $cliente->id,
                'produto_id' => $produto->id,
                'tipo_garantia' => fake()->randomElement(['produto', 'servico']),
                'data_inicio' => $dataInicio,
                'data_fim' => fake()->dateTimeBetween($dataInicio, $dataInicio->copy()->addMonths(18)),
                'descricao' => 'Garantia ' . $produto->nome,
                'condicoes' => fake('pt_BR')->paragraph,
                'ativa' => fake()->boolean(85),
            ]);
        }
    }

    private function createNotasFiscais(array $orcamentos, array $cadastros): void
    {
        $this->command->info('📄 Criando notas fiscais...');
        
        $orcamentosAprovados = array_filter($orcamentos, fn($o) => $o->status === 'aprovado');
        
        foreach ($orcamentosAprovados as $orcamento) {
            if (fake()->boolean(60)) { // 60% dos orçamentos aprovados têm NF
                $dataEmissao = fake()->dateTimeBetween($orcamento->data_orcamento, 'now');
                
                NotaFiscal::create([
                    'numero' => fake()->randomNumber(8, true),
                    'serie' => fake()->randomElement(['001', '002', '003']),
                    'cadastro_id' => $orcamento->cadastro_id,
                    'orcamento_id' => $orcamento->id,
                    'data_emissao' => $dataEmissao,
                    'data_vencimento' => fake()->dateTimeBetween($dataEmissao, '+1 month'),
                    'valor_total' => $orcamento->valor_total,
                    'valor_impostos' => $orcamento->valor_total * 0.15, // 15% de impostos
                    'status' => fake()->randomElement(['emitida', 'enviada', 'paga', 'cancelada']),
                    'chave_acesso' => fake()->regexify('[0-9]{44}'),
                    'observacoes' => fake('pt_BR')->optional(0.4)->sentence,
                ]);
            }
        }
    }

    private function createTarefas(array $usuarios, array $cadastros, array $ordensServico): void
    {
        $this->command->info('✅ Criando tarefas...');
        
        // Tarefas relacionadas a OSs
        foreach ($ordensServico as $os) {
            if (fake()->boolean(80)) { // 80% das OSs têm tarefas
                $numTarefas = fake()->numberBetween(1, 4);
                
                for ($i = 1; $i <= $numTarefas; $i++) {
                    $dataVencimento = fake()->dateTimeBetween($os->data_abertura, $os->data_prevista);
                    
                    Tarefa::create([
                        'titulo' => fake()->randomElement([
                            'Medição no local',
                            'Corte das peças',
                            'Instalação',
                            'Acabamento',
                            'Entrega',
                            'Revisão final'
                        ]),
                        'descricao' => fake('pt_BR')->sentence,
                        'cadastro_id' => $os->cadastro_id,
                        'ordem_servico_id' => $os->id,
                        'usuario_responsavel_id' => fake()->randomElement($usuarios)->id,
                        'data_vencimento' => $dataVencimento,
                        'data_conclusao' => fake()->optional(0.6)->dateTimeBetween($os->data_abertura, 'now'),
                        'prioridade' => fake()->randomElement(['baixa', 'media', 'alta', 'urgente']),
                        'status' => fake()->randomElement(['pendente', 'em_andamento', 'concluida', 'cancelada']),
                        'observacoes' => fake('pt_BR')->optional(0.4)->sentence,
                    ]);
                }
            }
        }
        
        // Tarefas administrativas
        for ($i = 1; $i <= 25; $i++) {
            $usuario = fake()->randomElement($usuarios);
            $cliente = fake()->optional(0.5)->randomElement($cadastros);
            
            Tarefa::create([
                'titulo' => fake()->randomElement([
                    'Ligar para cliente',
                    'Enviar orçamento',
                    'Acompanhar pagamento',
                    'Atualizar cadastro',
                    'Fazer follow-up',
                    'Organizar documentos'
                ]),
                'descricao' => fake('pt_BR')->sentence,
                'cadastro_id' => $cliente?->id,
                'usuario_responsavel_id' => $usuario->id,
                'data_vencimento' => fake()->dateTimeBetween('now', '+1 month'),
                'data_conclusao' => fake()->optional(0.4)->dateTimeBetween('-1 week', 'now'),
                'prioridade' => fake()->randomElement(['baixa', 'media', 'alta']),
                'status' => fake()->randomElement(['pendente', 'em_andamento', 'concluida']),
                'observacoes' => fake('pt_BR')->optional(0.3)->sentence,
            ]);
        }
    }

    private function showStatistics(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Estatísticas dos dados criados:');
        $this->command->table(['Entidade', 'Registros Criados'], [
            ['👤 Usuários', User::count()],
            ['👥 Cadastros', Cadastro::count()],
            ['📁 Categorias', Categoria::count()],
            ['📦 Produtos', Produto::count()],
            ['💰 Tabelas de Preço', TabelaPreco::count()],
            ['🔧 Equipamentos', Equipamento::count()],
            ['📊 Estoques', Estoque::count()],
            ['💭 Lista de Desejos', ListaDesejo::count()],
            ['💰 Orçamentos', Orcamento::count()],
            ['📋 Itens de Orçamento', OrcamentoItem::count()],
            ['🔧 Ordens de Serviço', OrdemServico::count()],
            ['📋 Itens de OS', OrdemServicoItem::count()],
            ['📅 Agendamentos', Agenda::count()],
            ['💳 Financeiros', Financeiro::count()],
            // ['💰 Transações Financeiras', TransacaoFinanceira::count()], // Removido - sistema legacy
            ['🛡️ Garantias', Garantia::count()],
            ['📄 Notas Fiscais', NotaFiscal::count()],
            ['✅ Tarefas', Tarefa::count()],
        ]);
        
        $this->command->newLine();
        $this->command->info('🎉 População de dados concluída com sucesso!');
        $this->command->warn('💡 Login padrão: admin@stofgard.com / admin123');
    }
}
