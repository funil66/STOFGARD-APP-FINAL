<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->boolean('pdf_incluir_pix')->default(true)->after('pix_qrcode_base64');
        });
    }

    public function down()
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn('pdf_incluir_pix');
        });
    }
};
