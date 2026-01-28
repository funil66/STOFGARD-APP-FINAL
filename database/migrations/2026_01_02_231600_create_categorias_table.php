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
        if (! Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table) {
                $table->id();
                $table->string('tipo'); // 'financeiro', 'produto', 'servico', etc
                $table->string('nome');
                $table->string('slug')->unique();
                $table->string('cor')->nullable(); // cor para gráficos
                $table->string('icone')->nullable(); // emoji ou classe ícone
                $table->text('descricao')->nullable();
                $table->boolean('ativo')->default(true);
                $table->integer('ordem')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tipo', 'ativo']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
