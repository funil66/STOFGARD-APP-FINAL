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
        Schema::table('ordens_servico', function (Blueprint $table) {
            if (!Schema::hasColumn('ordens_servico', 'descricao')) {
                $table->text('descricao')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            if (Schema::hasColumn('ordens_servico', 'descricao')) {
                $table->dropColumn('descricao');
            }
        });
    }
};
