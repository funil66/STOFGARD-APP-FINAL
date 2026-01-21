<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'features')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropColumn('features');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clientes') && ! Schema::hasColumn('clientes', 'features')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->json('features')->nullable()->after('arquivos');
            });
        }
    }
};
