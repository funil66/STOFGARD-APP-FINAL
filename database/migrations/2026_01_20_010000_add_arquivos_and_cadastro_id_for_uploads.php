<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('parceiros', 'arquivos')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->json('arquivos')->nullable()->after('observacoes');
                // MySQL does not support direct indexing of JSON columns; omit index to avoid errors
            });
        }

        if (! Schema::hasColumn('orcamentos', 'documentos')) {
            Schema::table('orcamentos', function (Blueprint $table) {
                $table->json('documentos')->nullable()->after('observacoes_internas');
                // MySQL does not support direct indexing of JSON columns; omit index to avoid errors
            });
        }

        Schema::table('transacoes_financeiras', function (Blueprint $table) {
            $table->string('cadastro_id')->nullable()->after('parceiro_id');
            $table->index('cadastro_id');
        });
    }

    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            $table->dropColumn('arquivos');
        });

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn('documentos');
        });

        Schema::table('transacoes_financeiras', function (Blueprint $table) {
            $table->dropIndex(['cadastro_id']);
            $table->dropColumn('cadastro_id');
        });
    }
};
