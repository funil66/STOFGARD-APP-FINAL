<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primeiro, remove colunas antigas se existirem
        if (Schema::hasColumn('ordens_servico', 'loja_id')) {
            Schema::table('ordens_servico', function (Blueprint $table) {
                $table->dropForeign(['loja_id']);
                $table->dropColumn('loja_id');
            });
        }

        if (Schema::hasColumn('ordens_servico', 'vendedor_id')) {
            Schema::table('ordens_servico', function (Blueprint $table) {
                $table->dropForeign(['vendedor_id']);
                $table->dropColumn('vendedor_id');
            });
        }

        // Depois, recria apontando para CADASTROS (Tabela Unificada)
        if (! Schema::hasColumn('ordens_servico', 'loja_id')) {
            Schema::table('ordens_servico', function (Blueprint $table) {
                $table->foreignId('loja_id')->nullable()->constrained('cadastros')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('ordens_servico', 'vendedor_id')) {
            Schema::table('ordens_servico', function (Blueprint $table) {
                $table->foreignId('vendedor_id')->nullable()->constrained('cadastros')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ordens_servico', 'loja_id')) {
            Schema::table('ordens_servico', function (Blueprint $table) {
                $table->dropForeign(['loja_id']);
                $table->dropColumn('loja_id');
            });
        }

        if (Schema::hasColumn('ordens_servico', 'vendedor_id')) {
            Schema::table('ordens_servico', function (Blueprint $table) {
                $table->dropForeign(['vendedor_id']);
                $table->dropColumn('vendedor_id');
            });
        }
    }
};
