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
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->timestamp('aprovado_em')->nullable()->after('status');
            $table->timestamp('reprovado_em')->nullable()->after('aprovado_em');
            $table->text('motivo_reprovacao')->nullable()->after('reprovado_em');
            $table->date('data_servico_agendada')->nullable()->after('motivo_reprovacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['aprovado_em', 'reprovado_em', 'motivo_reprovacao', 'data_servico_agendada']);
        });
    }
};
