<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use League\Csv\Writer;

class BackfillCadastroIds extends Command
{
    protected $signature = 'backfill:cadastro
        {--apply : Actually apply changes (default is dry-run)}
        {--rollback= : Path to CSV backup to rollback from}
        {--tables= : Comma-separated list of tables to process (defaults: orcamentos,ordens_servico,transacoes_financeiras,agendas,notas_fiscais)}
        {--force : Skip confirmation prompts when applying or rolling back}
    ';

    protected $description = 'Backfill missing cadastro_id values from existing cliente_id/parceiro_id across multiple tables (dry-run, apply, rollback with CSV)';

    protected array $defaultTables = [
        'orcamentos',
        'ordens_servico',
        'transacoes_financeiras',
        'agendas',
        'notas_fiscais',
    ];

    public function handle(): int
    {
        $rollbackPath = $this->option('rollback');
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $tablesOpt = $this->option('tables');

        $tables = $tablesOpt ? array_filter(array_map('trim', explode(',', $tablesOpt))) : $this->defaultTables;

        if ($rollbackPath) {
            return $this->handleRollback($rollbackPath, $force);
        }

        $reportRows = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->warn("Tabela não existe: {$table}. Pulando.");
                continue;
            }

            if (! Schema::hasColumn($table, 'cadastro_id')) {
                $this->warn("Tabela {$table} não tem coluna cadastro_id. Pulando.");
                continue;
            }

            $rows = DB::table($table)
                ->whereNull('cadastro_id')
                ->where(function ($q) {
                    $q->whereNotNull('cliente_id')
                      ->orWhereNotNull('parceiro_id');
                })
                ->get();

            foreach ($rows as $r) {
                $new = null;
                if (isset($r->cliente_id) && $r->cliente_id) {
                    $new = 'cliente_' . $r->cliente_id;
                } elseif (isset($r->parceiro_id) && $r->parceiro_id) {
                    $new = 'parceiro_' . $r->parceiro_id;
                }

                if ($new) {
                    $reportRows[] = [
                        'table' => $table,
                        'id' => $r->id,
                        'old_cadastro_id' => null,
                        'new_cadastro_id' => $new,
                    ];
                }
            }
        }

        if (empty($reportRows)) {
            $this->info('Nenhum registro encontrado para backfill. Nada a fazer.');
            return 0;
        }

        $timestamp = Carbon::now()->format('Ymd_His');
        $previewPath = storage_path("app/backfills/cadastro_backfill_preview_{$timestamp}.csv");
        $this->ensureDir(dirname($previewPath));

        $this->writeCsv($previewPath, $reportRows);
        $this->info("Relatório de dry-run gerado: {$previewPath}");

        $this->table(['table', 'id', 'new_cadastro_id'], array_map(fn($r) => [$r['table'], $r['id'], $r['new_cadastro_id']], $reportRows));

        if (! $apply) {
            $this->info('Executado em modo dry-run. Rode com --apply para aplicar as mudanças.');
            return 0;
        }

        if (! $force && ! $this->confirm('Deseja aplicar as mudanças mostradas acima?')) {
            $this->info('Abortando por confirmação negativa.');
            return 1;
        }

        // Backup CSV for rollback
        $backupPath = storage_path("app/backfills/cadastro_backfill_applied_{$timestamp}.csv");
        $this->ensureDir(dirname($backupPath));
        $backupRows = [];

        DB::beginTransaction();

        try {
            foreach ($reportRows as $row) {
                $table = $row['table'];
                $id = $row['id'];
                $new = $row['new_cadastro_id'];

                // read old value (should be null but capture anyway)
                $current = DB::table($table)->where('id', $id)->value('cadastro_id');

                // write backup row
                $backupRows[] = [
                    'table' => $table,
                    'id' => $id,
                    'old_cadastro_id' => $current,
                    'new_cadastro_id' => $new,
                ];

                // apply update
                DB::table($table)->where('id', $id)->update(['cadastro_id' => $new]);
            }

            DB::commit();

            // write backup CSV
            $this->writeCsv($backupPath, $backupRows);
            $this->info("Backfill aplicado com sucesso. Backup salvo em: {$backupPath}");

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Erro ao aplicar backfill: '.$e->getMessage());
            return 2;
        }
    }

    protected function ensureDir(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function writeCsv(string $path, array $rows): void
    {
        $fp = fopen($path, 'w');
        fputcsv($fp, ['table', 'id', 'old_cadastro_id', 'new_cadastro_id']);
        foreach ($rows as $row) {
            fputcsv($fp, [$row['table'], $row['id'], $row['old_cadastro_id'], $row['new_cadastro_id']]);
        }
        fclose($fp);
    }

    protected function handleRollback(string $path, bool $force): int
    {
        if (! file_exists($path)) {
            $this->error("Arquivo de backup não encontrado: {$path}");
            return 1;
        }

        $rows = array_map('str_getcsv', file($path));
        // first row is header
        array_shift($rows);

        $toRevert = [];
        foreach ($rows as $r) {
            [$table, $id, $old, $new] = $r + [null, null, null, null];
            $toRevert[] = ['table' => $table, 'id' => $id, 'old' => $old];
        }

        $this->table(['table','id','old_cadastro_id'], array_map(fn($r) => [$r['table'],$r['id'],$r['old']], $toRevert));

        if (! $force && ! $this->confirm('Deseja reverter os registros acima usando o backup?')) {
            $this->info('Rollback abortado.');
            return 1;
        }

        DB::beginTransaction();
        try {
            foreach ($toRevert as $r) {
                DB::table($r['table'])->where('id', $r['id'])->update(['cadastro_id' => $r['old']]);
            }
            DB::commit();
            $this->info('Rollback aplicado com sucesso.');
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Erro no rollback: '.$e->getMessage());
            return 2;
        }
    }
}
