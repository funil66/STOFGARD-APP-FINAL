<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration fixes a leftover state on SQLite where the conversion
        // created 'financeiros_new' but failed to rename it to 'financeiros'
        if (DB::getDriverName() === 'sqlite') {
            if (Schema::hasTable('financeiros_new') && ! Schema::hasTable('financeiros')) {
                // Drop any dependent views first
                DB::statement('DROP VIEW IF EXISTS financeiro_audit');

                Schema::dropIfExists('financeiros');
                Schema::rename('financeiros_new', 'financeiros');

                // Recreate the audit view if applicable
                $selects = [];

                if (Schema::hasTable('financeiros')) {
                    $selects[] = "SELECT 
                'financeiros' AS tabela,
                COUNT(*) AS total_registros,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) AS pagos,
                SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) AS total_entradas,
                SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) AS total_saidas,
                MAX(created_at) AS ultimo_registro
            FROM financeiros
            WHERE deleted_at IS NULL";
                }

                if (Schema::hasTable('transacoes_financeiras')) {
                    $selects[] = "SELECT 
                'transacoes_financeiras' AS tabela,
                COUNT(*) AS total_registros,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) AS pagos,
                SUM(CASE WHEN tipo = 'receita' THEN valor_total ELSE 0 END) AS total_entradas,
                SUM(CASE WHEN tipo = 'despesa' THEN valor_total ELSE 0 END) AS total_saidas,
                MAX(created_at) AS ultimo_registro
            FROM transacoes_financeiras
            WHERE deleted_at IS NULL";
                }

                if (! empty($selects)) {
                    $sql = 'CREATE VIEW financeiro_audit AS '.implode("\nUNION ALL\n", $selects);
                    DB::statement($sql);
                }

                echo "✅ SQLite: renamed 'financeiros_new' to 'financeiros' and recreated view if needed.\n";
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
