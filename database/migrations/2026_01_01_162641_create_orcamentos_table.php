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
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_orcamento')->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->date('data_orcamento');
            $table->date('data_validade'); // 7 dias a partir da data_orcamento

            // Serviço
            $table->string('tipo_servico', 255);
            $table->text('descricao_servico');
            $table->decimal('area_m2', 10, 2)->nullable(); // Área em m²

            // Valores
            $table->decimal('valor_m2', 10, 2)->default(0); // Valor por m²
            $table->decimal('valor_subtotal', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->enum('forma_pagamento', ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'boleto', 'transferencia'])->nullable();
            $table->boolean('desconto_pix_aplicado')->default(false); // 10% desconto

            // Status
            $table->enum('status', ['pendente', 'em_elaboracao', 'aprovado', 'recusado', 'expirado', 'convertido'])->default('pendente');
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->onDelete('set null');

            // Parceiro
            $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->onDelete('set null');
            $table->string('numero_pedido_parceiro')->nullable();

            // Observações
            $table->text('observacoes')->nullable();
            $table->text('observacoes_internas')->nullable();

            // Auditoria
            $table->string('criado_por');
            $table->string('atualizado_por')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('numero_orcamento');
            $table->index('cliente_id');
            $table->index('status');
            $table->index('data_orcamento');
            $table->index('data_validade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
