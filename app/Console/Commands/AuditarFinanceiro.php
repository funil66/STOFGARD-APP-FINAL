<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditarFinanceiro extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financeiro:auditar 
                            {--export : Exportar resultado para arquivo JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audita o sistema financeiro verificando integridade, duplicidades e problemas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 AUDITORIA DO SISTEMA FINANCEIRO - STOFGARD');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $report = [];

        // 1. Verificar estrutura da tabela financeiros
        $this->info('1️⃣  Verificando estrutura da tabela financeiros...');
        $structure = $this->checkFinanceirosStructure();
        $report['estrutura'] = $structure;
        $this->displayStructure($structure);

        // 2. Verificar view de auditoria
        $this->newLine();
        $this->info('2️⃣  Verificando view de auditoria...');
        $auditData = $this->checkAuditView();
        $report['audit_view'] = $auditData;
        $this->displayAuditData($auditData);

        // 3. Verificar integridade referencial
        $this->newLine();
        $this->info('3️⃣  Verificando integridade referencial...');
        $integrity = $this->checkIntegrity();
        $report['integridade'] = $integrity;
        $this->displayIntegrity($integrity);

        // 4. Verificar duplicidades
        $this->newLine();
        $this->info('4️⃣  Verificando duplicidades e inconsistências...');
        $duplicates = $this->checkDuplicates();
        $report['duplicidades'] = $duplicates;
        $this->displayDuplicates($duplicates);

        // 5. Verificar performance (índices)
        $this->newLine();
        $this->info('5️⃣  Verificando índices de performance...');
        $indexes = $this->checkIndexes();
        $report['indices'] = $indexes;
        $this->displayIndexes($indexes);

        // 6. Resumo final
        $this->newLine();
        $this->displaySummary($report);

        // Exportar se solicitado
        if ($this->option('export')) {
            $this->exportReport($report);
        }

        return Command::SUCCESS;
    }

    private function checkFinanceirosStructure(): array
    {
        $columns = Schema::getColumns('financeiros');

        $hasStringCadastroId = false;
        $hasIntegerCadastroId = false;
        $hasClienteId = false;
        $hasParceiroId = false;
        $hasCategoria = false;
        $hasCategoriaId = false;

        foreach ($columns as $column) {
            if ($column['name'] === 'cadastro_id') {
                $type = $column['type_name'];
                $hasIntegerCadastroId = in_array($type, ['bigint', 'int', 'integer']);
                $hasStringCadastroId = in_array($type, ['varchar', 'string', 'text']);
            }
            if ($column['name'] === 'cliente_id') {
                $hasClienteId = true;
            }
            if ($column['name'] === 'parceiro_id') {
                $hasParceiroId = true;
            }
            if ($column['name'] === 'categoria') {
                $hasCategoria = true;
            }
            if ($column['name'] === 'categoria_id') {
                $hasCategoriaId = true;
            }
        }

        return [
            'cadastro_id_integer' => $hasIntegerCadastroId,
            'cadastro_id_string' => $hasStringCadastroId,
            'cliente_id_legado' => $hasClienteId,
            'parceiro_id_legado' => $hasParceiroId,
            'categoria_string' => $hasCategoria,
            'categoria_id_fk' => $hasCategoriaId,
            'total_colunas' => count($columns),
        ];
    }

    private function checkAuditView(): array
    {
        try {
            $data = DB::select('SELECT * FROM financeiro_audit');

            return [
                'exists' => true,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkIntegrity(): array
    {
        // Verificar registros órfãos (sem cadastro válido)
        $orphans = DB::table('financeiros as f')
            ->leftJoin('cadastros as c', 'f.cadastro_id', '=', 'c.id')
            ->whereNotNull('f.cadastro_id')
            ->whereNull('c.id')
            ->count();

        // Verificar registros sem categoria
        $withoutCategoria = DB::table('financeiros')
            ->whereNull('categoria_id')
            ->count();

        // Verificar cadastro_id NULL
        $nullCadastro = DB::table('financeiros')
            ->whereNull('cadastro_id')
            ->count();

        $total = DB::table('financeiros')->count();

        return [
            'total_registros' => $total,
            'orfaos' => $orphans,
            'sem_categoria' => $withoutCategoria,
            'sem_cadastro' => $nullCadastro,
            'integridade_ok' => $orphans === 0,
        ];
    }

    private function checkDuplicates(): array
    {
        // Verificar Models duplicados
        $hasFinanceiroModel = file_exists(app_path('Models/Financeiro.php'));
        $hasTransacaoModel = file_exists(app_path('Models/TransacaoFinanceira.php'));

        // Verificar Resources duplicados
        $hasFinanceiroResource = file_exists(app_path('Filament/Resources/FinanceiroResource.php'));
        $hasTransacaoResource = file_exists(app_path('Filament/Resources/TransacaoFinanceiraResource.php'));

        // Verificar migrations desabilitadas
        $disabledMigrations = glob(database_path('migrations/DISABLED_*.php'));

        // Verificar tabela transacoes_financeiras
        $hasTransacoesTable = Schema::hasTable('transacoes_financeiras');
        $transacoesCount = 0;

        if ($hasTransacoesTable) {
            $transacoesCount = DB::table('transacoes_financeiras')->count();
        }

        return [
            'model_financeiro' => $hasFinanceiroModel,
            'model_transacao' => $hasTransacaoModel,
            'resource_financeiro' => $hasFinanceiroResource,
            'resource_transacao' => $hasTransacaoResource,
            'migrations_desabilitadas' => count($disabledMigrations),
            'tabela_transacoes_existe' => $hasTransacoesTable,
            'registros_transacoes' => $transacoesCount,
        ];
    }

    private function checkIndexes(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: usar pragma index_list
            $indexes = DB::select("PRAGMA index_list('financeiros')");

            $compositeIndexes = [];
            foreach ($indexes as $index) {
                if (! str_starts_with($index->name, 'sqlite_autoindex')) {
                    $columns = DB::select("PRAGMA index_info('{$index->name}')");
                    $compositeIndexes[$index->name] = array_map(fn ($col) => $col->name, $columns);
                }
            }
        } else {
            // MySQL/MariaDB
            $indexes = DB::select("
                SELECT DISTINCT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'financeiros'
                ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ");

            $compositeIndexes = [];
            foreach ($indexes as $index) {
                if ($index->INDEX_NAME !== 'PRIMARY') {
                    $compositeIndexes[$index->INDEX_NAME][] = $index->COLUMN_NAME;
                }
            }
        }

        // Verificar índices recomendados
        $hasRecommendedIndex = isset($compositeIndexes['idx_financeiros_cadastro_status_tipo']);

        return [
            'total_indices' => count($compositeIndexes),
            'indices' => $compositeIndexes,
            'has_recommended_index' => $hasRecommendedIndex,
            'driver' => $driver,
        ];
    }

    private function displayStructure(array $structure): void
    {
        $this->table(
            ['Item', 'Status'],
            [
                ['cadastro_id (integer FK)', $this->statusIcon($structure['cadastro_id_integer'])],
                ['cadastro_id (string) [LEGADO]', $this->statusIcon($structure['cadastro_id_string'], true)],
                ['cliente_id [LEGADO]', $this->statusIcon($structure['cliente_id_legado'], true)],
                ['parceiro_id [LEGADO]', $this->statusIcon($structure['parceiro_id_legado'], true)],
                ['categoria (string) [LEGADO]', $this->statusIcon($structure['categoria_string'], true)],
                ['categoria_id (FK)', $this->statusIcon($structure['categoria_id_fk'])],
            ]
        );
    }

    private function displayAuditData(array $auditData): void
    {
        if (! $auditData['exists']) {
            $this->error('❌ View financeiro_audit não encontrada!');
            $this->warn('Execute: php artisan migrate --path=database/migrations/2026_02_01_062327_create_financeiro_audit_view.php');

            return;
        }

        $headers = ['Tabela', 'Registros', 'Pendentes', 'Pagos', 'Entradas (R$)', 'Saídas (R$)'];
        $rows = [];

        foreach ($auditData['data'] as $row) {
            $rows[] = [
                $row->tabela,
                $row->total_registros,
                $row->pendentes,
                $row->pagos,
                'R$ '.number_format($row->total_entradas, 2, ',', '.'),
                'R$ '.number_format($row->total_saidas, 2, ',', '.'),
            ];
        }

        $this->table($headers, $rows);
    }

    private function displayIntegrity(array $integrity): void
    {
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de Registros', $integrity['total_registros']],
                ['Registros Órfãos (sem cadastro válido)', $this->colorize($integrity['orfaos'], 'danger')],
                ['Sem Categoria', $integrity['sem_categoria']],
                ['Sem Cadastro (NULL)', $integrity['sem_cadastro']],
                ['Integridade OK', $this->statusIcon($integrity['integridade_ok'])],
            ]
        );

        if ($integrity['orfaos'] > 0) {
            $this->error("⚠️  {$integrity['orfaos']} registros órfãos encontrados! Execute:");
            $this->line('   SELECT f.id, f.cadastro_id, f.descricao FROM financeiros f LEFT JOIN cadastros c ON f.cadastro_id = c.id WHERE f.cadastro_id IS NOT NULL AND c.id IS NULL;');
        }
    }

    private function displayDuplicates(array $duplicates): void
    {
        $this->table(
            ['Item', 'Status'],
            [
                ['Model Financeiro', $this->statusIcon($duplicates['model_financeiro'])],
                ['Model TransacaoFinanceira [LEGADO]', $this->statusIcon($duplicates['model_transacao'], true)],
                ['Resource Financeiro', $this->statusIcon($duplicates['resource_financeiro'])],
                ['Resource TransacaoFinanceira [LEGADO]', $this->statusIcon($duplicates['resource_transacao'], true)],
                ['Migrations Desabilitadas', $duplicates['migrations_desabilitadas']],
                ['Tabela transacoes_financeiras', $this->statusIcon($duplicates['tabela_transacoes_existe'], true)],
                ['Registros em transacoes_financeiras', $this->colorize($duplicates['registros_transacoes'], 'warning')],
            ]
        );

        if ($duplicates['registros_transacoes'] > 0) {
            $this->warn("⚠️  {$duplicates['registros_transacoes']} registros em transacoes_financeiras! Considere migrar.");
        }
    }

    private function displayIndexes(array $indexes): void
    {
        $rows = [];
        foreach ($indexes['indices'] as $name => $columns) {
            $rows[] = [$name, implode(', ', $columns)];
        }

        $this->table(['Índice', 'Colunas'], $rows);

        if (! $indexes['has_recommended_index']) {
            $this->warn('⚠️  Índice recomendado (cadastro_id, status, tipo) não encontrado!');
            $this->line('   Execute: php artisan migrate --path=database/migrations/2026_02_01_062306_add_composite_indexes_to_financeiros_table.php');
        }
    }

    private function displaySummary(array $report): void
    {
        $this->info('📊 RESUMO DA AUDITORIA');
        $this->info('═══════════════════════════════════════════════════');

        $issues = 0;
        $warnings = 0;
        $recommendations = [];

        // Avaliar estrutura
        if ($report['estrutura']['cadastro_id_string']) {
            $issues++;
            $recommendations[] = '🔴 CRÍTICO: cadastro_id ainda é string. Execute migration de conversão.';
        }
        if ($report['estrutura']['cliente_id_legado'] || $report['estrutura']['parceiro_id_legado']) {
            $warnings++;
            $recommendations[] = '🟡 Colunas legadas cliente_id/parceiro_id ainda existem.';
        }
        if ($report['estrutura']['categoria_string']) {
            $warnings++;
            $recommendations[] = '🟡 Coluna categoria (string) ainda existe.';
        }

        // Avaliar integridade
        if (! $report['integridade']['integridade_ok']) {
            $issues++;
            $recommendations[] = "🔴 CRÍTICO: {$report['integridade']['orfaos']} registros órfãos detectados!";
        }

        // Avaliar duplicidades
        if ($report['duplicidades']['registros_transacoes'] > 0) {
            $warnings++;
            $recommendations[] = "🟡 {$report['duplicidades']['registros_transacoes']} registros em transacoes_financeiras (legado).";
        }

        // Avaliar performance
        if (! $report['indices']['has_recommended_index']) {
            $warnings++;
            $recommendations[] = '🟡 Índices de performance não criados.';
        }

        // Exibir resultado
        if ($issues === 0 && $warnings === 0) {
            $this->info('✅ Sistema financeiro está CONSOLIDADO e SEM PROBLEMAS!');
            $this->line('   Todas as verificações passaram com sucesso.');
        } else {
            if ($issues > 0) {
                $this->error("🔴 {$issues} problema(s) crítico(s) encontrado(s)!");
            }
            if ($warnings > 0) {
                $this->warn("🟡 {$warnings} aviso(s) encontrado(s).");
            }

            $this->newLine();
            $this->info('📋 RECOMENDAÇÕES:');
            foreach ($recommendations as $rec) {
                $this->line("   {$rec}");
            }
        }

        $this->newLine();
        $this->info('📖 Consulte: docs/CONSOLIDACAO_FINANCEIRO_GUIA_EXECUCAO.md');
    }

    private function exportReport(array $report): void
    {
        $filename = storage_path('app/financeiro_audit_'.date('Y-m-d_His').'.json');
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("📁 Relatório exportado: {$filename}");
    }

    private function statusIcon(bool $status, bool $inverse = false): string
    {
        if ($inverse) {
            return $status ? '<fg=red>❌ Sim (LEGADO)</>' : '<fg=green>✅ Não</>';
        }

        return $status ? '<fg=green>✅ Sim</>' : '<fg=red>❌ Não</>';
    }

    private function colorize($value, string $level): string
    {
        if ($value == 0) {
            return "<fg=green>{$value}</>";
        }

        if ($level === 'danger') {
            return "<fg=red>{$value}</>";
        }

        return "<fg=yellow>{$value}</>";
    }
}
