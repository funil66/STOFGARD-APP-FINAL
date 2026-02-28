<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Remove tabela legacy transacoes_financeiras
     * O sistema agora usa apenas a tabela 'financeiros'
     */
    public function up(): void
    {
        // Drop view that depends on this table first (specifically for PostgreSQL)
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS financeiro_audit CASCADE');
        } else {
            \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS financeiro_audit');
        }

        Schema::dropIfExists('transacoes_financeiras');
        echo "✅ Tabela legacy 'transacoes_financeiras' removida com sucesso!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não vamos recriar a tabela legacy
        echo "⚠️  Rollback não implementado - tabela legacy não será recriada.\n";
    }
};
