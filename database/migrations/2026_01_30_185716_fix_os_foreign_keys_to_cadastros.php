<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            // Tenta remover chaves antigas se existirem
            try {
                $table->dropForeign(['loja_id']);
                $table->dropColumn('loja_id');
            } catch (\Exception $e) {
            }

            try {
                $table->dropForeign(['vendedor_id']);
                $table->dropColumn('vendedor_id');
            } catch (\Exception $e) {
            }
        });

        Schema::table('ordens_servico', function (Blueprint $table) {
            // Recria apontando para CADASTROS (Tabela Unificada)
            $table->foreignId('loja_id')->nullable()->constrained('cadastros')->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('cadastros')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropForeign(['loja_id']);
            $table->dropForeign(['vendedor_id']);
            $table->dropColumn(['loja_id', 'vendedor_id']);
        });
    }
};
