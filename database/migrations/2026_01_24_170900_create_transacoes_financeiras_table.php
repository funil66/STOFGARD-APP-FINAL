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
        Schema::create('transacoes_financeiras', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['receita', 'despesa']);
            $table->enum('status', ['pendente', 'pago', 'atrasado', 'cancelado'])->default('pendente');
            $table->string('categoria'); // Ex: Serviço, Comissão, Material, Combustível
            $table->string('descricao');
            $table->decimal('valor_previsto', 10, 2); // Valor do orçamento/boleto
            $table->decimal('valor_realizado', 10, 2)->nullable(); // Valor efetivamente pago
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->string('forma_pagamento')->nullable(); // Pix, Boleto, etc
            $table->string('comprovante_path')->nullable(); // Upload de foto/pdf
            $table->text('observacoes')->nullable();

            // Campos de rastreabilidade polimórficos
            $table->string('origem_type')->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();

            $table->unsignedBigInteger('created_by'); // Quem lançou
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacoes_financeiras');
    }
};
