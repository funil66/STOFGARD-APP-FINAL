<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->decimal('desconto_pix', 5, 2)->default(10.00);
            $table->json('taxas_parcelamento')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracaos', function (Blueprint $table) {
            $table->dropColumn(['desconto_pix', 'taxas_parcelamento']);
        });
    }
};
