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
            $table->string('empresa_nome')->default('Stofgard');
            $table->string('empresa_cnpj')->nullable();
            $table->string('empresa_telefone')->nullable();
            $table->string('empresa_logo')->nullable();
            $table->text('pdf_header')->nullable();
            $table->text('pdf_footer')->nullable();
            $table->text('termos_garantia')->nullable();
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
            $table->dropColumn([
                'empresa_nome',
                'empresa_cnpj',
                'empresa_telefone',
                'empresa_logo',
                'pdf_header',
                'pdf_footer',
                'termos_garantia',
            ]);
        });
    }
};
