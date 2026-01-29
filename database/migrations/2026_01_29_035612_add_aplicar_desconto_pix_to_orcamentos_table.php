<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            // Adiciona somente se não existir (idempotente)
            if (! Schema::hasColumn('orcamentos', 'aplicar_desconto_pix')) {
                $table->boolean('aplicar_desconto_pix')->default(true)->after('valor_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            if (Schema::hasColumn('orcamentos', 'aplicar_desconto_pix')) {
                $table->dropColumn('aplicar_desconto_pix');
            }
        });
    }
};
