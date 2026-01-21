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
        Schema::create('orcamentos_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->onDelete('cascade');
            $table->foreignId('tabela_preco_id')->nullable()->constrained('tabela_precos')->onDelete('set null');
            $table->string('descricao_item')->comment('Nome do item/serviço');
            $table->enum('unidade_medida', ['unidade', 'm2'])->default('unidade');
            $table->decimal('quantidade', 10, 2)->default(1);
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2)->storedAs('quantidade * valor_unitario');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            // Índices
            $table->index('orcamento_id');
            $table->index('tabela_preco_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orcamentos_itens');
    }
};
