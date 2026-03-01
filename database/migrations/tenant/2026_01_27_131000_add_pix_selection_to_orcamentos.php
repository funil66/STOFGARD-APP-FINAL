<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // Armazena a chave PIX (string) escolhida para este orçamento
            $table->string('pix_chave_selecionada')->nullable()->after('pdf_incluir_pix');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn('pix_chave_selecionada');
        });
    }
};
