<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Atualiza Orçamentos (Adiciona Vendedor e Loja)
        Schema::table('orcamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('orcamentos', 'vendedor_id')) {
                if (Schema::hasTable('cadastros')) {
                    $table->foreignId('vendedor_id')->nullable()->constrained('cadastros')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('vendedor_id')->nullable();
                }
            }
            if (! Schema::hasColumn('orcamentos', 'loja_id')) {
                if (Schema::hasTable('cadastros')) {
                    $table->foreignId('loja_id')->nullable()->constrained('cadastros')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('loja_id')->nullable();
                }
            }
        });

        // 2. Cria Tabela de Ordens de Serviço
        if (! Schema::hasTable('ordem_servicos')) {
            Schema::create('ordem_servicos', function (Blueprint $table) {
                $table->id();
                $table->string('numero_os')->unique(); // Ex: OS-2026.001

                // Vínculos
                $table->foreignId('orcamento_id')->constrained('orcamentos');
                if (Schema::hasTable('cadastros')) {
                    $table->foreignId('cadastro_id')->constrained('cadastros');
                    $table->foreignId('vendedor_id')->nullable()->constrained('cadastros');
                    $table->foreignId('loja_id')->nullable()->constrained('cadastros');
                } else {
                    $table->unsignedBigInteger('cadastro_id');
                    $table->unsignedBigInteger('vendedor_id')->nullable();
                    $table->unsignedBigInteger('loja_id')->nullable();
                }

                // Dados Operacionais
                $table->date('data_agendamento')->nullable();
                $table->string('periodo')->nullable();
                $table->string('status')->default('pendente');
                $table->decimal('valor_total', 15, 2);
                $table->text('observacoes_tecnicas')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servicos');
        Schema::table('orcamentos', function (Blueprint $table) {
            if (Schema::hasColumn('orcamentos', 'vendedor_id')) {
                $table->dropForeign(['vendedor_id']);
            }
            if (Schema::hasColumn('orcamentos', 'loja_id')) {
                $table->dropForeign(['loja_id']);
            }
            $table->dropColumn(['vendedor_id', 'loja_id']);
        });
    }
};
