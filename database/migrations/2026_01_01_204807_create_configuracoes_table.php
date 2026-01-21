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
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 50)->index(); // empresa, financeiro, whatsapp, nfse, sistema, notificacoes
            $table->string('chave', 100)->index();
            $table->text('valor')->nullable();
            $table->string('tipo', 20)->default('text'); // text, number, boolean, json, file
            $table->text('descricao')->nullable();
            $table->timestamps();

            // Única combinação de grupo + chave
            $table->unique(['grupo', 'chave']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
