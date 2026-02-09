<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->boolean('aplicar_desconto_pix')->default(true);
            $table->boolean('repassar_taxas')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['aplicar_desconto_pix', 'repassar_taxas']);
        });
    }
};
