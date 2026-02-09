<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->json('status_orcamento_personalizado')->nullable(); // Ex: {'pendente': 'Pendente', 'aprovado': 'Aprovado'}
            $table->json('formas_pagamento_personalizado')->nullable(); // Ex: {'pix': 'Pix', 'credito': 'Crédito'}
            $table->json('cores_pdf')->nullable(); // Ex: {'primaria': '#000000', 'secundaria': '#555555'}
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configuracaos', function (Blueprint $table) {
            $table->dropColumn(['status_orcamento_personalizado', 'formas_pagamento_personalizado', 'cores_pdf']);
        });
    }
};
