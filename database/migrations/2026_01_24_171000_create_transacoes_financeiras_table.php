<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            if (! Schema::hasTable('transacoes_financeiras')) {
                Schema::create('transacoes_financeiras', function (Blueprint $table) {
                    $table->id();
                    $table->string('descricao'); // Ex: Pagamento OS #123
                    $table->enum('tipo', ['receita', 'despesa']);
                    $table->decimal('valor_previsto', 10, 2);
                    $table->decimal('valor_pago', 10, 2)->nullable(); // Se null, não foi pago total
                    $table->date('data_vencimento');
                    $table->date('data_pagamento')->nullable();
                    $table->enum('status', ['pendente', 'pago', 'parcial', 'atrasado', 'cancelado'])->default('pendente');
                    $table->string('categoria'); // Ex: Serviço, Comissão, Material, Aluguel
                    $table->string('forma_pagamento')->nullable(); // Pix, Dinheiro, Boleto
                    $table->string('comprovante_path')->nullable(); // Upload
                    $table->text('observacoes')->nullable();

                    // Relacionamentos Polimórficos
                    $table->nullableMorphs('origem'); // Cria origem_id e origem_type

                    // Relacionamentos Diretos
                    $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
                    $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->nullOnDelete(); // Para comissões

                    $table->timestamps();
                    $table->softDeletes();
                });
            }
        } catch (\Throwable $e) {
            // Ignore if table already exists or other creation errors
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transacoes_financeiras');
    }
};