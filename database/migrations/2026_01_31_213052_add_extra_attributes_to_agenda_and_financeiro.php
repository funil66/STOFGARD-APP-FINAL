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
        Schema::table('agendas', function (Blueprint $table) {
            $table->json('extra_attributes')->nullable()->after('observacoes');
        });

        Schema::table('financeiros', function (Blueprint $table) {
            $table->json('extra_attributes')->nullable()->after('observacoes'); // Assuming observacoes exists, or put at end
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');
        });

        Schema::table('financeiros', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');
        });
    }
};
