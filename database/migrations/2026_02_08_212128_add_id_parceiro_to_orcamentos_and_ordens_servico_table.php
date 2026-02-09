<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('id_parceiro')->nullable()->after('loja_id')->comment('Campo manual para identificar loja/vendedor parceiro');
        });

        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('id_parceiro')->nullable()->after('loja_id')->comment('Campo manual para identificar loja/vendedor parceiro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn('id_parceiro');
        });

        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn('id_parceiro');
        });
    }
};
