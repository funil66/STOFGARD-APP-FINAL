<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ordem_servicos', function (Blueprint $table) {
            $table->string('assinatura_cliente_path')->nullable()->after('status');
            $table->dateTime('data_conclusao')->nullable()->after('assinatura_cliente_path');
        });
    }

    public function down()
    {
        Schema::table('ordem_servicos', function (Blueprint $table) {
            $table->dropColumn(['assinatura_cliente_path', 'data_conclusao']);
        });
    }
};
