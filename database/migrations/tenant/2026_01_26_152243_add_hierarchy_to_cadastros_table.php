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
        Schema::table('clientes', function (Blueprint $table) {
            // Garante que o campo tipo exista e seja indexado
            if (! Schema::hasColumn('clientes', 'tipo')) {
                $table->string('tipo')->default('cliente')->index()->after('id');
            }

            // Campo para vincular Vendedor -> Loja
            if (! Schema::hasColumn('clientes', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->comment('Vinculo hierarquico: Vendedor -> Loja')
                    ->constrained('clientes')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'parent_id')) {
                // Drop constrained foreign id if possible
                try {
                    $table->dropForeign(['parent_id']);
                } catch (\Throwable $e) {
                    // ignore if constraint does not exist
                }
                $table->dropColumn('parent_id');
            }

            if (Schema::hasColumn('clientes', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
