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
        Schema::table('agendas', function (Blueprint $table) {
            $table->json('extra_attributes')->nullable()->after('observacoes');
        });

        if (Schema::hasTable('financeiros')) {
            Schema::table('financeiros', function (Blueprint $table) {
                if (! Schema::hasColumn('financeiros', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('observacoes'); // Assuming observacoes exists, or put at end
                }
            });
        } elseif (Schema::hasTable('transacoes_financeiras')) {
            Schema::table('transacoes_financeiras', function (Blueprint $table) {
                if (! Schema::hasColumn('transacoes_financeiras', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('observacoes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');
        });

        if (Schema::hasTable('financeiros') && Schema::hasColumn('financeiros', 'extra_attributes')) {
            Schema::table('financeiros', function (Blueprint $table) {
                $table->dropColumn('extra_attributes');
            });
        } elseif (Schema::hasTable('transacoes_financeiras') && Schema::hasColumn('transacoes_financeiras', 'extra_attributes')) {
            Schema::table('transacoes_financeiras', function (Blueprint $table) {
                $table->dropColumn('extra_attributes');
            });
        }
    }
};
