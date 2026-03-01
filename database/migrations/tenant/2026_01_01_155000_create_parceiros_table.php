<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parceiros', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['loja', 'vendedor']); // Tipo de parceiro

            // Dados básicos
            $table->string('nome');
            $table->string('razao_social')->nullable();
            $table->string('cnpj_cpf')->nullable();

            // Contato
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('celular')->nullable();

            // Endereço (para lojas)
            $table->string('cep')->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();

            // Configurações comerciais
            $table->decimal('percentual_comissao', 5, 2)->default(10.00); // Percentual de comissão padrão
            $table->boolean('ativo')->default(true);

            // Estatísticas
            $table->integer('total_vendas')->default(0);
            $table->decimal('total_comissoes', 10, 2)->default(0);

            // Observações
            $table->text('observacoes')->nullable();

            // Auditoria
            $table->string('registrado_por');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('nome');
            $table->index('tipo');
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parceiros');
    }
};
