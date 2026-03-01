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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // Dados Pessoais
            $table->string('nome'); // Obrigatório
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('celular')->nullable();
            $table->string('cpf_cnpj')->nullable();

            // Endereço
            $table->string('cep')->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();

            // Observações e Arquivos
            $table->text('observacoes')->nullable();
            $table->json('arquivos')->nullable(); // Array de caminhos de arquivos

            // Auditoria
            $table->string('registrado_por')->nullable(); // Iniciais do usuário

            $table->timestamps();
            $table->softDeletes();

            // Índices para busca
            $table->index('nome');
            $table->index('email');
            $table->index('telefone');
            $table->index('celular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
