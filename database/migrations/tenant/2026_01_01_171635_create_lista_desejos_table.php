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
        Schema::create('lista_desejos', function (Blueprint $table) {
            $table->id();

            // Item desejado
            $table->string('nome');
            $table->text('descricao')->nullable();

            // Categoria
            $table->enum('categoria', [
                'quimico',
                'equipamento',
                'acessorio',
                'ferramenta',
                'epi',
                'consumivel',
                'outro',
            ]);

            // Quantidade
            $table->integer('quantidade_desejada')->default(1);
            $table->string('unidade')->default('un');

            // Preços estimados
            $table->decimal('preco_estimado', 10, 2)->nullable();
            $table->decimal('valor_total_estimado', 10, 2)->nullable();

            // Prioridade
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'urgente'])->default('media');

            // Status
            $table->enum('status', ['pendente', 'orcamento', 'aprovado', 'comprado', 'recusado'])->default('pendente');

            // Fornecedor sugerido
            $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->onDelete('set null');

            // Justificativa
            $table->text('justificativa')->nullable();
            $table->text('observacoes')->nullable();

            // Link/referência
            $table->string('link_referencia')->nullable();

            // Aprovação
            $table->string('aprovado_por', 10)->nullable();
            $table->date('data_aprovacao')->nullable();
            $table->date('data_compra')->nullable();

            // Controle
            $table->string('solicitado_por', 10)->nullable();
            $table->string('atualizado_por', 10)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
            $table->index('prioridade');
            $table->index('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_desejos');
    }
};
