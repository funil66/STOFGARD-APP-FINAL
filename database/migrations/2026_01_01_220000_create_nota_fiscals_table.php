<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_fiscals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->nullOnDelete();
            $table->string('numero_nf')->unique();
            $table->string('serie')->nullable();
            $table->enum('tipo', ['entrada', 'saida'])->default('saida');
            $table->enum('modelo', ['NFe', 'NFSe', 'NFCe'])->default('NFe');
            $table->date('data_emissao');
            $table->string('chave_acesso')->nullable()->unique();
            $table->string('protocolo_autorizacao')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->decimal('valor_produtos', 10, 2)->default(0);
            $table->decimal('valor_servicos', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('valor_icms', 10, 2)->default(0);
            $table->decimal('valor_iss', 10, 2)->default(0);
            $table->decimal('valor_pis', 10, 2)->default(0);
            $table->decimal('valor_cofins', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->enum('status', ['rascunho', 'emitida', 'cancelada', 'denegada'])->default('rascunho');
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('data_cancelamento')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_fiscals');
    }
};
