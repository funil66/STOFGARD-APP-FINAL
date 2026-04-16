<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfis_garantia', function (Blueprint $table) {
            $table->string('tamanho_fonte')->nullable()->default('10px');
            $table->string('familia_fonte')->nullable()->default('Arial, sans-serif');
        });
    }

    public function down(): void
    {
        Schema::table('perfis_garantia', function (Blueprint $table) {
            $table->dropColumn(['tamanho_fonte', 'familia_fonte']);
        });
    }
};
