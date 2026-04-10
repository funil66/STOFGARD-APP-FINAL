<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfis_garantia', function (Blueprint $table) {
            $table->string('titulo_certificado')->nullable()->after('termos_legais');
            $table->string('subtitulo_certificado')->nullable()->after('titulo_certificado');
            $table->string('titulo_termos_garantia')->nullable()->after('subtitulo_certificado');
            $table->text('texto_rodape_certificado')->nullable()->after('titulo_termos_garantia');
        });
    }

    public function down(): void
    {
        Schema::table('perfis_garantia', function (Blueprint $table) {
            $table->dropColumn([
                'titulo_certificado',
                'subtitulo_certificado',
                'titulo_termos_garantia',
                'texto_rodape_certificado',
            ]);
        });
    }
};
