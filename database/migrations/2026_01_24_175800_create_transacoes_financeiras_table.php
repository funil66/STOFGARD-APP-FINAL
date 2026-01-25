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
        // If table already exists, skip
        if (Schema::hasTable('transacoes_financeiras')) {
            return;
        }

        try {
            if (! Schema::hasTable('transacoes_financeiras')) {
                Schema::create('transacoes_financeiras', function (Blueprint $table) {
                    $table->id();
                    $table->string('descricao');
                    $table->enum('tipo', ['receita', 'despesa']);
                    $table->decimal('valor_previsto', 10, 2);
                    $table->decimal('valor_pago', 10, 2)->nullable();
                    $table->date('data_vencimento');
                    $table->date('data_pagamento')->nullable();
                    $table->enum('status', ['pendente', 'pago', 'parcial', 'atrasado', 'cancelado'])->default('pendente');
                    $table->string('categoria')->default('Geral');
                    $table->string('forma_pagamento')->nullable();
                    $table->string('comprovante_path')->nullable();
                    $table->text('observacoes')->nullable();

                    // Relacionamento Polimórfico
                    $table->nullableMorphs('origem');

                    $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
                    $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->nullOnDelete();

                    $table->timestamps();
                    $table->softDeletes();
                });
            }
        } catch (\Throwable $e) {
            // ignore if exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacoes_financeiras');
    }
};
