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
            // Adiciona se não existir
            if (!Schema::hasColumn('ordens_servico', 'data_inicio')) {
                $table->dateTime('data_inicio')->nullable();
            }
            if (!Schema::hasColumn('ordens_servico', 'data_fim')) {
                $table->dateTime('data_fim')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn(['data_inicio', 'data_fim']);
        });
    }
};
