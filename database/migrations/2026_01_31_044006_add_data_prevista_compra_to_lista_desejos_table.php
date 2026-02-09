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
        Schema::table('lista_desejos', function (Blueprint $table) {
            if (!Schema::hasColumn('lista_desejos', 'data_prevista_compra')) {
                $table->date('data_prevista_compra')->nullable()->after('data_compra');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lista_desejos', function (Blueprint $table) {
            $table->dropColumn('data_prevista_compra');
        });
    }
};
