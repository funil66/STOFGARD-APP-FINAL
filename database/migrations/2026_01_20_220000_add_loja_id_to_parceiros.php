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
        Schema::table('parceiros', function (Blueprint $table) {
            if (! Schema::hasColumn('parceiros', 'loja_id')) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id')->index();
                $table->foreign('loja_id')->references('id')->on('parceiros')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            if (Schema::hasColumn('parceiros', 'loja_id')) {
                $table->dropForeign(['loja_id']);
                $table->dropColumn('loja_id');
            }
        });
    }
};