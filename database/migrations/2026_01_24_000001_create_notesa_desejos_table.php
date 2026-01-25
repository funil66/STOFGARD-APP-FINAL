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
        if (! Schema::hasTable('lista_desejos')) {
            Schema::create('lista_desejos', function (Blueprint $table) {
                $table->id();
                $table->string('item'); // Nome do item da lista de desejos
                $table->decimal('valor_estimado', 10, 2); // Valor estimado do item
                $table->date('data_prevista'); // Data prevista para aquisição
                $table->string('link_referencia')->nullable(); // Link de referência
                $table->enum('prioridade', ['alta', 'media', 'baixa'])->default('media'); // Prioridade do item
                $table->enum('status', ['pendente', 'comprado', 'cancelado'])->default('pendente'); // Status do item
                $table->text('observacoes')->nullable(); // Observações adicionais
                $table->timestamps(); // Timestamps padrão
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lista_desejos');
    }
};