<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona campos de precificação interna ao estoque.
     * Permite controle de custo e cálculo de margem.
     */
    public function up(): void
    {
        Schema::table('estoques', function (Blueprint $table) {
            // Preço interno (custo de aquisição)
            $table->decimal('preco_interno', 12, 2)->nullable()->after('tipo');

            // Preço de venda de referência
            $table->decimal('preco_venda', 12, 2)->nullable()->after('preco_interno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estoques', function (Blueprint $table) {
            $table->dropColumn(['preco_interno', 'preco_venda']);
        });
    }
};
