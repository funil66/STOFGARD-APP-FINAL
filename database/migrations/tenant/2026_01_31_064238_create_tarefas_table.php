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
        Schema::create('tarefas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();

            // Status e Prioridade
            $table->enum('status', ['pendente', 'em_andamento', 'concluida', 'cancelada'])->default('pendente');
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media');

            // Datas
            $table->dateTime('data_vencimento')->nullable();
            $table->dateTime('data_conclusao')->nullable();

            // Pessoas
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('criado_por')->constrained('users');

            // Polimorfismo (Opcional - pode estar ligado a um Cliente, Orçamento, OS, etc)
            $table->nullableMorphs('relacionado');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarefas');
    }
};
