<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('clientes')) {
            return;
        }

        if (! Schema::hasColumn('clientes', 'features')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->json('features')->nullable()->after('arquivos');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('clientes')) {
            return;
        }

        if (Schema::hasColumn('clientes', 'features')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropColumn('features');
            });
        }
    }
};
