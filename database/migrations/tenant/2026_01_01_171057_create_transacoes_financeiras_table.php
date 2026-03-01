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

            // Tipo da transação
            $table->enum('tipo', ['receita', 'despesa']); // receita ou despesa

            // Informações básicas
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->date('data_transacao');
            $table->date('data_vencimento')->nullable();
            $table->date('data_pagamento')->nullable();

            // Categoria
            $table->enum('categoria', [
                'servico',           // Receita de serviços
                'produto',           // Venda de produtos
                'comissao',          // Comissões pagas
                'salario',           // Salários
                'fornecedor',        // Pagamento a fornecedores
                'aluguel',           // Aluguel
                'energia',           // Energia elétrica
                'agua',              // Água
                'internet',          // Internet
                'telefone',          // Telefone
                'combustivel',       // Combustível
                'manutencao',        // Manutenção
                'marketing',         // Marketing
                'impostos',          // Impostos
                'equipamentos',      // Equipamentos
                'material',          // Material de consumo
                'outros',             // Outros
            ]);

            // Status
            $table->enum('status', ['pendente', 'pago', 'vencido', 'cancelado'])->default('pendente');

            // Método de pagamento
            $table->enum('metodo_pagamento', [
                'dinheiro',
                'pix',
                'cartao_credito',
                'cartao_debito',
                'transferencia',
                'boleto',
                'cheque',
                'outro',
            ])->nullable();

            // Relacionamentos
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->onDelete('set null');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->onDelete('set null');

            // Parcelamento
            $table->integer('parcela_numero')->nullable(); // Ex: 1, 2, 3
            $table->integer('parcela_total')->nullable();  // Ex: 3 (de 3 parcelas)
            $table->foreignId('transacao_pai_id')->nullable()->constrained('transacoes_financeiras')->onDelete('cascade');

            // Observações
            $table->text('observacoes')->nullable();
            $table->string('comprovante')->nullable(); // Path do arquivo de comprovante

            // Controle
            $table->boolean('conciliado')->default(false);
            $table->string('criado_por', 10)->nullable();
            $table->string('atualizado_por', 10)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tipo');
            $table->index('status');
            $table->index('categoria');
            $table->index('data_transacao');
            $table->index('data_vencimento');
            $table->index('data_pagamento');
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
