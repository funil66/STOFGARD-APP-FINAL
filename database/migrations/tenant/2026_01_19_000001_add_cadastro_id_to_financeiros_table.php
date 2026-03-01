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
        if (Schema::hasTable('financeiros')) {
            Schema::table('financeiros', function (Blueprint $table) {
                if (! Schema::hasColumn('financeiros', 'cadastro_id')) {
                    $table->unsignedBigInteger('cadastro_id')->nullable()->after('cliente_id');
                    $table->index('cadastro_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financeiros', function (Blueprint $table) {
            $table->dropIndex(['cadastro_id']);
            $table->dropColumn('cadastro_id');
        });
    }
};
