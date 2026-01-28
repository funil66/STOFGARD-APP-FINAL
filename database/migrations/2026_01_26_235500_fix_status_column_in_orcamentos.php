<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // Garante que é string de 50 caracteres (suficiente para 'rascunho', 'aprovado')
            $table->string('status', 50)->change();

            // Garante que valor_total aceite decimais corretamente
            $table->decimal('valor_total', 15, 2)->change();
        });
    }

    public function down(): void
    {
        // Não faz nada no down (alterações não revertidas automaticamente)
    }
};
