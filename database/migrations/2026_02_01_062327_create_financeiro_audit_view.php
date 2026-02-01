<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cria view de auditoria para monitorar registros nos sistemas financeiros paralelos.
     * Útil para verificar qual tabela está sendo utilizada e planejar consolidação.
     */
    public function up(): void
    {
        // Remover view se já existir (compatível com SQLite)
        DB::statement("DROP VIEW IF EXISTS financeiro_audit");

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

        if (empty($selects)) {
            // Nenhuma tabela financeira disponível — nada a criar
            return;
        }

        $sql = 'CREATE VIEW financeiro_audit AS ' . implode("\nUNION ALL\n", $selects);
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS financeiro_audit");
    }
};
