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
        if (Schema::hasTable('financeiros') && ! Schema::hasColumn('financeiros', 'categoria_id')) {
            Schema::table('financeiros', function (Blueprint $table) {
                $table->foreignId('categoria_id')
                    ->nullable()
                    ->after('categoria')
                    ->constrained('categorias')
                    ->nullOnDelete();

                $table->index('categoria_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('financeiros', 'categoria_id')) {
            Schema::table('financeiros', function (Blueprint $table) {
                $table->dropForeign(['categoria_id']);
                $table->dropColumn('categoria_id');
            });
        }
    }
};
