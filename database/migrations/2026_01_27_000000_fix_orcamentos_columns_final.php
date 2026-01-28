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
                // Tenta dropar chave estrangeira se existir, ignorando erro se não existir
                try {
                    $table->dropForeign(['cliente_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('cliente_id');
            }

            // 2. Garante que status cabe a palavra "rascunho" e "aprovado"
            if (Schema::hasColumn('orcamentos', 'status')) {
                $table->string('status', 50)->change();
            }
        });
    }

    public function down(): void
    {
        // Irreversível neste contexto
    }
};
