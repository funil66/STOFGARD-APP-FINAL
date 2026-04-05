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
        Schema::table('lista_desejos', function (Blueprint $table) {
            $table->string('aprovado_por', 255)->nullable()->change();
            $table->string('solicitado_por', 255)->nullable()->change();
            $table->string('atualizado_por', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lista_desejos', function (Blueprint $table) {
            $table->string('aprovado_por', 10)->nullable()->change();
            $table->string('solicitado_por', 10)->nullable()->change();
            $table->string('atualizado_por', 10)->nullable()->change();
        });
    }
};
