<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis_garantia', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->integer('dias_garantia');
            $table->longText('termos_legais')->nullable(); // (CAMPO QUE O USUARIO VAI COLOCAR OS TERMOS LEGAIS E TUDO MAIS)
            $table->timestamps();
        });

        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->foreignId('perfil_garantia_id')->nullable()->constrained('perfis_garantia')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropForeign(['perfil_garantia_id']);
            $table->dropColumn('perfil_garantia_id');
        });
        Schema::dropIfExists('perfis_garantia');
    }
};
