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
        Schema::table('tabela_precos', function (Blueprint $table) {
            if (! Schema::hasColumn('tabela_precos', 'configuracao_id')) {
                $table->foreignId('configuracao_id')->default(1)->constrained('configuracoes');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tabela_precos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('configuracao_id');
        });
    }
};
