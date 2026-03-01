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
        Schema::table('orcamentos', function (Blueprint $table) {
            // Valor final após edição pelo usuário
            $table->decimal('valor_final_editado', 10, 2)->nullable()->after('valor_total');

            // Desconto aplicado pelo prestador (diferença entre valor_total e valor_final_editado)
            $table->decimal('desconto_prestador', 10, 2)->default(0)->after('valor_final_editado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['valor_final_editado', 'desconto_prestador']);
        });
    }
};
