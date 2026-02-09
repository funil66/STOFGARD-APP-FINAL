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
        Schema::create('sequencias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50)->comment('Tipo de documento: orcamento, os, nf, etc');
            $table->year('ano')->comment('Ano da sequência');
            $table->unsignedInteger('ultimo_numero')->default(0)->comment('Último número gerado');
            $table->timestamps();

            // Índice único para evitar duplicatas
            $table->unique(['tipo', 'ano']);

            // Índice para performance
            $table->index(['tipo', 'ano']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequencias');
    }
};
