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
        Schema::create('tabela_precos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_servico', ['higienizacao', 'impermeabilizacao'])->index();
            $table->string('categoria', 100); // Ex: "Cadeiras e Poltronas", "Sofás"
            $table->string('nome_item', 255);
            $table->enum('unidade_medida', ['unidade', 'm2'])->default('unidade');
            $table->decimal('preco_vista', 10, 2);
            $table->decimal('preco_prazo', 10, 2);
            $table->boolean('ativo')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para performance
            $table->index(['tipo_servico', 'ativo']);
            $table->index('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabela_precos');
    }
};
