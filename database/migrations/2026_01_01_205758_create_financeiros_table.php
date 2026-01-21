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
        Schema::create('financeiros', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->onDelete('set null');
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->onDelete('set null');

            // Dados básicos
            $table->enum('tipo', ['entrada', 'saida'])->default('entrada');
            $table->string('descricao');
            $table->text('observacoes')->nullable();
            $table->string('categoria', 100)->nullable();

            // Valores
            $table->decimal('valor', 10, 2);
            $table->decimal('valor_pago', 10, 2)->nullable();
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('juros', 10, 2)->default(0);
            $table->decimal('multa', 10, 2)->default(0);

            // Datas
            $table->date('data');
            $table->date('data_vencimento')->nullable();
            $table->datetime('data_pagamento')->nullable();

            // Status e forma de pagamento
            $table->enum('status', ['pendente', 'pago', 'cancelado', 'atrasado'])->default('pendente');
            $table->string('forma_pagamento', 50)->nullable(); // dinheiro, pix, cartao, boleto, etc
            $table->string('comprovante')->nullable();

            // Campos PIX
            $table->string('pix_txid', 100)->nullable()->unique();
            $table->text('pix_qrcode_base64')->nullable();
            $table->text('pix_copia_cola')->nullable();
            $table->string('pix_location', 255)->nullable();
            $table->datetime('pix_expiracao')->nullable();
            $table->string('pix_status', 50)->nullable();
            $table->text('pix_response')->nullable();
            $table->datetime('pix_data_pagamento')->nullable();
            $table->decimal('pix_valor_pago', 10, 2)->nullable();

            // Link público de pagamento
            $table->string('link_pagamento_hash', 100)->nullable()->unique();

            $table->timestamps();

            // Índices
            $table->index('data');
            $table->index('status');
            $table->index('tipo');
            $table->index('data_vencimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financeiros');
    }
};
