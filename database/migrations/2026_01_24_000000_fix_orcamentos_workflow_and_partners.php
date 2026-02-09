<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // 1. Pagamento opcional no início
            $table->string('forma_pagamento')->nullable()->change();

            // 2. Integração com Parceiros (se ainda não existir)
            if (!Schema::hasColumn('orcamentos', 'parceiro_id')) {
                $table->foreignId('parceiro_id')->nullable()->constrained('parceiros')->nullOnDelete();
            }
        });

        Schema::table('orcamentos_itens', function (Blueprint $table) {
            // 3. Tipo de serviço no item (para saber se é Higi ou Imper)
            if (!Schema::hasColumn('orcamentos_itens', 'tipo_servico')) {
                if (Schema::hasColumn('orcamentos_itens', 'item')) {
                    $table->string('tipo_servico')->nullable()->after('item');
                } else {
                    $table->string('tipo_servico')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // Reverter alterações de 'forma_pagamento'
            $table->string('forma_pagamento')->nullable(false)->change();

            // Remover coluna 'parceiro_id'
            if (Schema::hasColumn('orcamentos', 'parceiro_id')) {
                $table->dropConstrainedForeignId('parceiro_id');
            }
        });

        Schema::table('orcamentos_itens', function (Blueprint $table) {
            // Remover coluna 'tipo_servico'
            if (Schema::hasColumn('orcamentos_itens', 'tipo_servico')) {
                $table->dropColumn('tipo_servico');
            }
        });
    }
};