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
            // Se a coluna existir, torna ela anulável
            if (Schema::hasColumn('ordens_servico', 'cliente_id')) {
                $table->unsignedBigInteger('cliente_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter (opcional): deixar vazio para não forçar mudança reversa
    }
};
