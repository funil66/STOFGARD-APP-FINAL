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
        Schema::create('garantias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->string('tipo_servico', 255);
            $table->date('data_inicio'); // Data conclusão do serviço
            $table->date('data_fim'); // Calculada automaticamente
            $table->integer('dias_garantia'); // 90 ou 365
            $table->enum('status', ['ativa', 'vencida', 'utilizada', 'cancelada'])->default('ativa');
            $table->text('observacoes')->nullable();
            $table->date('usado_em')->nullable(); // Quando cliente acionou
            $table->text('motivo_uso')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'data_fim']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garantias');
    }
};
