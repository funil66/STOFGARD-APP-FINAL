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
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'empresa_nome')) {
                $table->string('empresa_nome')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'empresa_cnpj')) {
                $table->string('empresa_cnpj')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'empresa_telefone')) {
                $table->string('empresa_telefone')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'empresa_email')) {
                $table->string('empresa_email')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'desconto_pix')) {
                $table->decimal('desconto_pix', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'taxas_parcelamento')) {
                $table->json('taxas_parcelamento')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'opcoes_pagamento_personalizado')) {
                $table->json('opcoes_pagamento_personalizado')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'cores_pdf')) {
                $table->json('cores_pdf')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'termos_garantia')) {
                $table->text('termos_garantia')->nullable();
            }
            if (! Schema::hasColumn('configuracoes', 'empresa_logo')) {
                $table->text('empresa_logo')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->dropColumn([
                'empresa_nome',
                'empresa_cnpj',
                'empresa_telefone',
                'empresa_email',
                'desconto_pix',
                'taxas_parcelamento',
                'opcoes_pagamento_personalizado',
                'cores_pdf',
                'termos_garantia',
                'empresa_logo',
            ]);
        });
    }
};