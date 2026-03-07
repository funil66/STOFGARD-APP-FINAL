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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('local_estoque_id')->nullable()->constrained('locais_estoque')->nullOnDelete();
        });

        Schema::table('estoques', function (Blueprint $table) {
            $table->foreignId('local_estoque_id')->nullable()->constrained('locais_estoque')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estoques', function (Blueprint $table) {
            $table->dropForeign(['local_estoque_id']);
            $table->dropColumn('local_estoque_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['local_estoque_id']);
            $table->dropColumn('local_estoque_id');
        });
    }
};
