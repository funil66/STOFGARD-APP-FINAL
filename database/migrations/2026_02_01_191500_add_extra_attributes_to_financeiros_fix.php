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
        if (Schema::hasTable('financeiros') && !Schema::hasColumn('financeiros', 'extra_attributes')) {
            Schema::table('financeiros', function (Blueprint $table) {
                $table->json('extra_attributes')->nullable()->after('observacoes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('financeiros') && Schema::hasColumn('financeiros', 'extra_attributes')) {
            Schema::table('financeiros', function (Blueprint $table) {
                $table->dropColumn('extra_attributes');
            });
        }
    }
};