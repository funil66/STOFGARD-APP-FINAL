<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financeiros')) {
            return;
        }

        Schema::table('financeiros', function (Blueprint $table) {
            if (! Schema::hasColumn('financeiros', 'parceiro_id')) {
                // Add parceiro_id for legacy compatibility
                $table->foreignId('parceiro_id')->nullable()->after('cliente_id')->constrained('parceiros')->nullOnDelete();
            }

            // Replace numeric cadastro_id column with a string-based one for unified cadastro ids (cliente_1, parceiro_1)
            if (Schema::hasColumn('financeiros', 'cadastro_id')) {
                try {
                    $table->dropIndex(['cadastro_id']);
                } catch (\Exception $e) {
                    // ignore if index doesn't exist
                }

                try {
                    $table->dropColumn('cadastro_id');
                } catch (\Exception $e) {
                    // ignore if drop not supported on the platform; we'll add a new column below
                }
            }

            if (! Schema::hasColumn('financeiros', 'cadastro_id')) {
                $table->string('cadastro_id')->nullable()->after('cliente_id');
                $table->index('cadastro_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('financeiros')) {
            return;
        }

        Schema::table('financeiros', function (Blueprint $table) {
            if (Schema::hasColumn('financeiros', 'parceiro_id')) {
                $table->dropForeign(['parceiro_id']);
                $table->dropColumn('parceiro_id');
            }

            if (Schema::hasColumn('financeiros', 'cadastro_id')) {
                try {
                    $table->dropIndex(['cadastro_id']);
                } catch (\Exception $e) {
                }
                try {
                    $table->dropColumn('cadastro_id');
                } catch (\Exception $e) {
                }
            }
        });
    }
};