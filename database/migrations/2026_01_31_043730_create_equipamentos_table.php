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
        Schema::create('equipamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('codigo_patrimonio')->nullable()->unique();
            $table->enum('status', ['ativo', 'manutencao', 'baixado'])->default('ativo');
            $table->date('data_aquisicao')->nullable();
            $table->decimal('valor_aquisicao', 10, 2)->nullable();
            $table->string('localizacao')->nullable(); // Onde está guardado
            $table->text('observacoes')->nullable();
            $table->string('criado_por')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipamentos');
    }
};
