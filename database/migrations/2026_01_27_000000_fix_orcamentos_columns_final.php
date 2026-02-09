<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // 1. Remove a coluna antiga que está quebrando o insert
            if (Schema::hasColumn('orcamentos', 'cliente_id')) {
                try {
                    // Remove the index explicitly if it exists
                    if (Schema::hasIndex('orcamentos', 'orcamentos_cliente_id_index')) {
                        $table->dropIndex('orcamentos_cliente_id_index');
                    }

                    // Tenta dropar chave estrangeira se existir
                    $table->dropForeign(['cliente_id']);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to drop foreign key or index for cliente_id: ' . $e->getMessage());
                }

                try {
                    // Tenta dropar a coluna se existir
                    $table->dropColumn('cliente_id');
                } catch (\Throwable $e) {
                    \Log::warning('Failed to drop column cliente_id: ' . $e->getMessage());
                }
            }

            // 2. Garante que status cabe a palavra "rascunho" e "aprovado"
            if (Schema::hasColumn('orcamentos', 'status')) {
                try {
                    $table->string('status', 50)->change();
                } catch (\Throwable $e) {
                    \Log::warning('Failed to change status column: ' . $e->getMessage());
                }
            }
        });
    }

    public function down(): void
    {
        // Irreversível neste contexto
    }
};
