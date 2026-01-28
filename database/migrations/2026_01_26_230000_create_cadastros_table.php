<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cadastros')) {
            Schema::create('cadastros', function (Blueprint $table) {
                $table->id();
                $table->string('nome')->index();
                $table->string('tipo')->default('cliente')->index(); // cliente, loja, vendedor, arquiteto
                $table->foreignId('parent_id')->nullable()->constrained('cadastros')->nullOnDelete();
                $table->string('documento')->nullable();
                $table->string('rg_ie')->nullable();
                $table->string('email')->nullable();
                $table->string('telefone')->nullable();
                $table->string('telefone_fixo')->nullable();
                $table->string('cep')->nullable();
                $table->string('logradouro')->nullable();
                $table->string('numero')->nullable();
                $table->string('bairro')->nullable();
                $table->string('cidade')->nullable();
                $table->string('estado')->nullable();
                $table->text('complemento')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cadastros')) {
            Schema::dropIfExists('cadastros');
        }
    }
};
