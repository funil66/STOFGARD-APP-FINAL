<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. No Cadastro (Vendedor/Loja): Define a % de comissão padrão dele
        if (Schema::hasTable('cadastros')) {
            Schema::table('cadastros', function (Blueprint $table) {
                if (! Schema::hasColumn('cadastros', 'comissao_percentual')) {
                    $table->decimal('comissao_percentual', 5, 2)->default(0)->after('tipo');
                }
            });
        }

        // 2. No Orçamento: Salva a comissão negociada e o toggle do PIX
        Schema::table('orcamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('orcamentos', 'comissao_vendedor')) {
                $table->decimal('comissao_vendedor', 10, 2)->default(0); // Valor em R$
            }
            if (! Schema::hasColumn('orcamentos', 'comissao_loja')) {
                $table->decimal('comissao_loja', 10, 2)->default(0); // Valor em R$
            }
            if (! Schema::hasColumn('orcamentos', 'pdf_incluir_pix')) {
                $table->boolean('pdf_incluir_pix')->default(true); // Toggle ON/OFF
            }
        });
    }

    public function down(): void
    {
        // Irreversível
    }
};
