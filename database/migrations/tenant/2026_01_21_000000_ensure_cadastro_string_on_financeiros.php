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
