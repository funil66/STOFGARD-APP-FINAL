<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Adiciona comissão no cadastro (Causa do erro atual)
        Schema::table('cadastros', function (Blueprint $table) {
            if (! Schema::hasColumn('cadastros', 'comissao_percentual')) {
                $table->decimal('comissao_percentual', 5, 2)->default(0)->after('tipo');
            }
        });

        // 2. Garante campos no orçamento
        Schema::table('orcamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('orcamentos', 'comissao_vendedor')) {
                $table->decimal('comissao_vendedor', 10, 2)->default(0); // Valor em R$
            }
            if (! Schema::hasColumn('orcamentos', 'comissao_loja')) {
                $table->decimal('comissao_loja', 10, 2)->default(0); // Valor em R$
            }
            if (! Schema::hasColumn('orcamentos', 'pdf_incluir_pix')) {
                $table->boolean('pdf_incluir_pix')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            if (Schema::hasColumn('orcamentos', 'comissao_vendedor')) {
                $table->dropColumn('comissao_vendedor');
            }
            if (Schema::hasColumn('orcamentos', 'comissao_loja')) {
                $table->dropColumn('comissao_loja');
            }
            if (Schema::hasColumn('orcamentos', 'pdf_incluir_pix')) {
                $table->dropColumn('pdf_incluir_pix');
            }
        });

        Schema::table('cadastros', function (Blueprint $table) {
            if (Schema::hasColumn('cadastros', 'comissao_percentual')) {
                $table->dropColumn('comissao_percentual');
            }
        });
    }
};
