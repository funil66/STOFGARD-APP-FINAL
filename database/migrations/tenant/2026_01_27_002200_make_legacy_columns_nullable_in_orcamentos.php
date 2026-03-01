<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            if (Schema::hasColumn('orcamentos', 'criado_por')) {
                $table->string('criado_por')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'atualizado_por')) {
                $table->string('atualizado_por')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'valor_desconto')) {
                $table->decimal('valor_desconto', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'area_m2')) {
                $table->decimal('area_m2', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'valor_m2')) {
                $table->decimal('valor_m2', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'forma_pagamento')) {
                $table->string('forma_pagamento')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'pix_chave_tipo')) {
                $table->string('pix_chave_tipo')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'pix_chave_valor')) {
                $table->string('pix_chave_valor')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'pix_txid')) {
                $table->string('pix_txid')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'pix_qrcode_base64')) {
                $table->text('pix_qrcode_base64')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'pix_copia_cola')) {
                $table->string('pix_copia_cola')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'link_pagamento_hash')) {
                $table->string('link_pagamento_hash')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'aprovado_em')) {
                $table->timestamp('aprovado_em')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'reprovado_em')) {
                $table->timestamp('reprovado_em')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'motivo_reprovacao')) {
                $table->string('motivo_reprovacao')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'data_servico_agendada')) {
                $table->timestamp('data_servico_agendada')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'numero_pedido_parceiro')) {
                $table->string('numero_pedido_parceiro')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'observacoes_internas')) {
                $table->text('observacoes_internas')->nullable()->change();
            }
            if (Schema::hasColumn('orcamentos', 'documentos')) {
                $table->text('documentos')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Irreversível
    }
};
