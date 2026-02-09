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
        Schema::create('ordem_servico_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')
                ->constrained('ordens_servico')
                ->cascadeOnDelete();
            $table->foreignId('estoque_id')
                ->constrained('estoques')
                ->cascadeOnDelete();
            $table->decimal('quantidade_utilizada', 10, 2);
            $table->string('unidade')->nullable(); // Cópia da unidade do estoque para histórico
            $table->text('observacao')->nullable();
            $table->timestamps();

            // Índices para melhor performance em consultas
            $table->index(['ordem_servico_id', 'estoque_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_estoque');
    }
};
