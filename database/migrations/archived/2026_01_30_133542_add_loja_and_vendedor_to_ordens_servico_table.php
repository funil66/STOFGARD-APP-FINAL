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
            if (!Schema::hasColumn('ordens_servico', 'loja_id')) {
                $table->foreignId('loja_id')->nullable()->constrained('parceiros')->nullOnDelete();
            }
            if (!Schema::hasColumn('ordens_servico', 'vendedor_id')) {
                $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('ordens_servico', 'origem')) {
                $table->string('origem')->default('direta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            //
        });
    }
};
