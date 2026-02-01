<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove tabela legacy transacoes_financeiras
     * O sistema agora usa apenas a tabela 'financeiros'
     */
    public function up(): void
    {
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
