<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the 'financeiros' table required by the Financeiro model.
     * This table stores financial transactions (accounts receivable/payable).
     */
    public function up(): void
    {
        if (Schema::hasTable('financeiros')) {
            return;
        }

        Schema::create('financeiros', function (Blueprint $table) {
            $table->id();

            // Cadastro Unificado (Cliente/Loja/Vendedor)
            $table->unsignedBigInteger('cadastro_id')->nullable();
            $table->foreign('cadastro_id')->references('id')->on('cadastros')->nullOnDelete();

            // Legacy support (optional, for migration from old data)
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('parceiro_id')->nullable();

            // Vínculos com OS e Orçamento
            $table->unsignedBigInteger('orcamento_id')->nullable();
            $table->foreign('orcamento_id')->references('id')->on('orcamentos')->nullOnDelete();

            $table->unsignedBigInteger('ordem_servico_id')->nullable();
            $table->foreign('ordem_servico_id')->references('id')->on('ordens_servico')->nullOnDelete();

            // Tipo da transação
            $table->enum('tipo', ['entrada', 'saida'])->default('entrada')->index();

            // Descrição e Categoria
            $table->string('descricao');
            $table->string('categoria')->nullable()->index(); // servico, material, comissao, etc.
            $table->text('observacoes')->nullable();

            // Valores
            $table->decimal('valor', 12, 2)->default(0);
            $table->decimal('valor_pago', 12, 2)->nullable();
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('juros', 10, 2)->default(0);
            $table->decimal('multa', 10, 2)->default(0);

            // Datas
            $table->date('data')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->datetime('data_pagamento')->nullable();

            // Status
            $table->enum('status', ['pendente', 'pago', 'atrasado', 'cancelado'])->default('pendente')->index();

            // Pagamento
            $table->string('forma_pagamento')->nullable();
            $table->string('comprovante')->nullable();

            // Campos PIX (para cobranças automáticas)
            $table->string('pix_txid')->nullable()->unique();
            $table->text('pix_qrcode_base64')->nullable();
            $table->text('pix_copia_cola')->nullable();
            $table->string('pix_location')->nullable();
            $table->datetime('pix_expiracao')->nullable();
            $table->string('pix_status')->nullable(); // ativo, pago, expirado, etc.
            $table->json('pix_response')->nullable();
            $table->datetime('pix_data_pagamento')->nullable();
            $table->decimal('pix_valor_pago', 12, 2)->nullable();

            // Link de pagamento (para envio via WhatsApp)
            $table->string('link_pagamento_hash')->nullable()->unique();

            $table->timestamps();
            $table->softDeletes();

            // Índices compostos para otimização
            $table->index(['status', 'data_vencimento']);
            $table->index(['tipo', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiros');
    }
};
