<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('notas_fiscais')) {
            // Nota: em alguns ambientes (ex: testes sqlite) a tabela pode não existir — pular migrate com segurança
            return;
        }

        if (Schema::hasColumn('notas_fiscais', 'cadastro_id')) {
            return;
        }

        Schema::table('notas_fiscais', function (Blueprint $table) {
            $table->string('cadastro_id')->nullable()->index()->after('cliente_id');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('notas_fiscais')) {
            return;
        }

        if (! Schema::hasColumn('notas_fiscais', 'cadastro_id')) {
            return;
        }

        Schema::table('notas_fiscais', function (Blueprint $table) {
            $table->dropColumn('cadastro_id');
        });
    }
};
