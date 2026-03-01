<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('pix_chave_tipo')->nullable()->after('desconto_pix_aplicado'); // 'cnpj' ou 'telefone'
            $table->string('pix_chave_valor')->nullable()->after('pix_chave_tipo');
            $table->string('pix_txid')->nullable()->after('pix_chave_valor');
            $table->text('pix_qrcode_base64')->nullable()->after('pix_txid');
            $table->text('pix_copia_cola')->nullable()->after('pix_qrcode_base64');
            $table->string('link_pagamento_hash')->nullable()->after('pix_copia_cola');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn([
                'pix_chave_tipo',
                'pix_chave_valor',
                'pix_txid',
                'pix_qrcode_base64',
                'pix_copia_cola',
                'link_pagamento_hash',
            ]);
        });
    }
};
