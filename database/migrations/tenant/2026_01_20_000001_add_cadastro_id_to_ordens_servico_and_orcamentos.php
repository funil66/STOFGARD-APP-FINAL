<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('cadastro_id')->nullable()->after('cliente_id');
            $table->index('cadastro_id');
        });
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('cadastro_id')->nullable()->after('cliente_id');
            $table->index('cadastro_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropIndex(['cadastro_id']);
            $table->dropColumn('cadastro_id');
        });
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropIndex(['cadastro_id']);
            $table->dropColumn('cadastro_id');
        });
    }
};
