<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de Extrato Financeiro (Ledger)
        if (! Schema::hasTable('movimentacoes_financeiras')) {
            Schema::create('movimentacoes_financeiras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete(); // Parceiro/Cliente
                $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete(); // Origem
                $table->string('tipo'); // 'credito' (comissão) ou 'debito' (saque/pagamento)
                $table->decimal('valor', 10, 2);
                $table->text('descricao')->nullable(); // Ex: "Comissão OS #123"
                $table->timestamps();
            });
        }

        // Adicionar campo de % de comissão no Cliente (se não existir)
        Schema::table('clientes', function (Blueprint $table) {
            if (! Schema::hasColumn('clientes', 'comissao_percentual')) {
                $table->decimal('comissao_percentual', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('clientes', 'dados_bancarios')) {
                $table->text('dados_bancarios')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('movimentacoes_financeiras')) {
            Schema::dropIfExists('movimentacoes_financeiras');
        }

        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'comissao_percentual')) {
                $table->dropColumn('comissao_percentual');
            }
            if (Schema::hasColumn('clientes', 'dados_bancarios')) {
                $table->dropColumn('dados_bancarios');
            }
        });
    }
};
