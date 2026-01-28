<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;

return new class extends Migration { public function up(): void { // 1. Categorias Financeiras (Ex: Serviços, Impostos, Material)
        if (! Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->enum('tipo', ['receita', 'despesa'])->index();
                $table->string('cor')->nullable(); // Para gráficos
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        // 2. Transações Financeiras (O Coração)
        if (! Schema::hasTable('transacoes_financeiras')) {
            Schema::create('transacoes_financeiras', function (Blueprint $table) {
                $table->id();
                $table->string('descricao'); // O que é?
                
                // Valores
                $table->decimal('valor_total', 10, 2);
                $table->decimal('valor_pago', 10, 2)->default(0);
                
                // Datas
                $table->date('data_vencimento');
                $table->date('data_pagamento')->nullable();
                
                // Status e Tipo
                $table->enum('tipo', ['receita', 'despesa'])->index();
                $table->enum('status', ['pendente', 'pago', 'atrasado', 'cancelado'])->default('pendente')->index();
                
                // Relacionamentos (O Elo Perdido)
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
                $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
                $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->nullOnDelete();
                
                // Polimorfismo para vincular a Cliente ou Parceiro (Cadastro)
                $table->foreignId('cadastro_id')->nullable()->constrained('cadastros')->nullOnDelete();
                
                $table->text('observacoes')->nullable();
                $table->string('comprovante_path')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
public function down(): void
{
    Schema::dropIfExists('transacoes_financeiras');
    Schema::dropIfExists('categorias');
}
};
