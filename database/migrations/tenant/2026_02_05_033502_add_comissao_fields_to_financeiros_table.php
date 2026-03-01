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
        Schema::table('financeiros', function (Blueprint $table) {
            $table->boolean('is_comissao')->default(false)->after('tipo');
            $table->boolean('comissao_paga')->default(false)->after('is_comissao');
            $table->datetime('comissao_data_pagamento')->nullable()->after('comissao_paga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financeiros', function (Blueprint $table) {
            $table->dropColumn(['is_comissao', 'comissao_paga', 'comissao_data_pagamento']);
        });
    }
};
