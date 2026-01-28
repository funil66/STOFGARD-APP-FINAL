<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // Remove a coluna que está travando o INSERT
            if (Schema::hasColumn('orcamentos', 'tipo_servico')) {
                $table->dropColumn('tipo_servico');
            }

            // Remove outras colunas antigas se existirem (Limpeza preventiva)
            if (Schema::hasColumn('orcamentos', 'descricao_servico')) {
                $table->dropColumn('descricao_servico');
            }

            // Garante que numero_orcamento seja nullable se ele ainda existir
            if (Schema::hasColumn('orcamentos', 'numero_orcamento')) {
                $table->string('numero_orcamento')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Irreversível - não recriamos colunas removidas
    }
};
