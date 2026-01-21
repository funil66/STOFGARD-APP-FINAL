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
        // Corrigir foreign key em financeiros
        Schema::table('financeiros', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordens_servico')->onDelete('set null');
        });

        // Corrigir foreign key em agendas
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordens_servico')->onDelete('set null');
        });

        // Corrigir foreign key em nota_fiscals
        Schema::table('nota_fiscals', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordens_servico')->onDelete('set null');
        });

        // Corrigir foreign key em garantias
        Schema::table('garantias', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordens_servico')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Reverter para os nomes errados (caso necessário)
        Schema::table('financeiros', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servicos')->onDelete('set null');
        });

        Schema::table('agendas', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servicos')->onDelete('set null');
        });

        Schema::table('nota_fiscals', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servicos')->onDelete('set null');
        });

        Schema::table('garantias', function (Blueprint $table) {
            $table->dropForeign(['ordem_servico_id']);
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servicos')->onDelete('cascade');
        });
    }
};
