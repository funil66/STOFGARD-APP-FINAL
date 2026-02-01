<?php

namespace App\Console\Commands;

use Database\Seeders\CompleteTestDataSeeder;
use Illuminate\Console\Command;

class PopulateTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-test-data 
                            {--force : Força a execução sem confirmar}
                            {--clear : Limpa dados existentes antes de popular}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popular o banco de dados com dados completos de teste para desenvolvimento';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Verificar ambiente
        if (!app()->environment(['local', 'testing'])) {
            $this->error('❌ Este comando só pode ser executado em ambiente local ou de teste!');
            return Command::FAILURE;
        }

        // Mostrar informações sobre o que será feito
        $this->info('🚀 Comando para Popular Dados de Teste - STOFGARD');
        $this->newLine();
        
        $this->warn('⚠️  ATENÇÃO: Este comando irá criar centenas de registros de teste!');
        $this->line('📊 Serão criados:');
        $this->line('   • ~70 Cadastros (clientes, vendedores, arquitetos, lojas)');
        $this->line('   • ~30 Produtos com tabelas de preço');
        $this->line('   • ~50 Orçamentos com itens');
        $this->line('   • ~30 Ordens de Serviço');
        $this->line('   • ~40 Agendamentos');
        $this->line('   • ~200 Movimentações Financeiras');
        $this->line('   • E muito mais...');
        $this->newLine();

        // Confirmar execução
        if (!$this->option('force')) {
            if (!$this->confirm('Deseja continuar?', false)) {
                $this->info('Operação cancelada.');
                return Command::SUCCESS;
            }
        }

        // Executar seeder
        try {
            $this->info('🔄 Iniciando população de dados...');
            $this->newLine();

            $seeder = new CompleteTestDataSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->newLine();
            $this->info('✅ Dados de teste populados com sucesso!');
            $this->newLine();
            
            $this->comment('💡 Dicas:');
            $this->line('   • Acesse: http://localhost/admin');
            $this->line('   • Login: admin@stofgard.com / admin123');
            $this->line('   • Explore os diferentes módulos do sistema');
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao popular dados: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
