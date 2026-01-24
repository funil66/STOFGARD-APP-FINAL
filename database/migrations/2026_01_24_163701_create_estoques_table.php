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
        Schema::create('estoques', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('item'); // Nome do produto
            $table->decimal('quantidade', 10, 2)->default(0); // Quantidade atual
            $table->enum('unidade', ['unidade', 'litros', 'caixa', 'metro'])->default('unidade'); // Unidade de medida
            $table->decimal('minimo_alerta', 10, 2)->default(5); // Quantidade mínima para alerta
            $table->string('tipo')->default('geral'); // Tipo do item (químico ou geral)
            $table->text('observacoes')->nullable(); // Observações adicionais
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoques');
    }
};
