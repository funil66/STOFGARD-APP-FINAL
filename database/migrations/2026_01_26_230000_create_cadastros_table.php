<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cadastros');
        DB::statement('DROP VIEW IF EXISTS cadastros');

        Schema::create('cadastros', function (Blueprint $table) {
            $table->id();

            // Relacionamento Hierárquico (Pai/Filho)
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('loja_id')->nullable()->index(); // Mantendo por compatibilidade

            $table->string('nome');
            $table->string('nome_fantasia')->nullable();

            // Documentos
            $table->string('documento')->nullable()->unique();
            $table->string('rg_ie')->nullable();

            // Contato
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('celular')->nullable();

            // Endereço
            $table->string('cep')->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();

            // Tipo e Status (Adicionado 'vendedor' e 'loja')
            $table->enum('tipo', ['cliente', 'parceiro', 'fornecedor', 'loja', 'vendedor'])->default('cliente')->index();
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();

            // Financeiro / Comissões
            $table->decimal('comissao_fixa', 10, 2)->nullable();
            $table->decimal('comissao_percentual', 5, 2)->nullable(); // Campo novo detectado no erro

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastros');
    }
};
