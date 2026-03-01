<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ensure no null celular values exist before making the column NOT NULL
        \Illuminate\Support\Facades\DB::table('clientes')->whereNull('celular')->update(['celular' => '']);

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('telefone')->nullable()->change();
            $table->string('celular')->nullable(false)->change(); // Garante que celular seja obrigatório
        });

        Schema::table('parceiros', function (Blueprint $table) {
            $table->string('telefone')->nullable()->change();
            $table->string('celular')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('telefone')->nullable(false)->change(); // Reverte para não nulo
            $table->string('celular')->nullable()->change(); // Reverte para opcional
        });

        Schema::table('parceiros', function (Blueprint $table) {
            $table->string('telefone')->nullable(false)->change(); // Reverte para não nulo
            $table->string('celular')->nullable()->change(); // Reverte para opcional
        });
    }
};
