<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->string('numero_os')->unique(); // Número único da OS
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // Informações do serviço
            $table->string('tipo_servico'); // Higienização, Impermeabilização, etc
            $table->text('descricao_servico');
            $table->date('data_abertura');
            $table->date('data_prevista')->nullable();
            $table->date('data_conclusao')->nullable();

            // Status da OS
            $table->enum('status', [
                'aberta',
                'em_andamento',
                'aguardando_pecas',
                'concluida',
                'cancelada',
            ])->default('aberta');

            // Parceiro (loja/vendedor)
            $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->nullOnDelete();
            $table->string('numero_pedido_parceiro')->nullable(); // Número do pedido na loja parceira
            $table->decimal('comissao_parceiro', 10, 2)->nullable()->default(0);

            // Valores
            $table->decimal('valor_servico', 10, 2)->default(0);
            $table->decimal('valor_produtos', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);

            // Forma de pagamento
            $table->enum('forma_pagamento', [
                'dinheiro',
                'pix',
                'cartao_credito',
                'cartao_debito',
                'boleto',
                'transferencia',
            ])->nullable();

            $table->boolean('pagamento_realizado')->default(false);

            // Garantia
            $table->integer('dias_garantia')->default(90); // 90 dias padrão
            $table->date('data_fim_garantia')->nullable();

            // Fotos antes e depois
            $table->json('fotos_antes')->nullable();
            $table->json('fotos_depois')->nullable();

            // Observações e anotações
            $table->text('observacoes')->nullable();
            $table->text('observacoes_internas')->nullable();

            // Produtos utilizados
            $table->json('produtos_utilizados')->nullable(); // Array de produtos do almoxarifado

            // Avaliação do cliente
            $table->integer('avaliacao')->nullable(); // 1 a 5 estrelas
            $table->text('comentario_cliente')->nullable();

            // Auditoria
            $table->string('criado_por'); // Iniciais do usuário
            $table->string('atualizado_por')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices para buscas
            $table->index('numero_os');
            $table->index('cliente_id');
            $table->index('status');
            $table->index('data_abertura');
            $table->index('parceiro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_servico');
    }
};
