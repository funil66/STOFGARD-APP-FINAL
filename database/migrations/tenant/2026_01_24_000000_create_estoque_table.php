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
        Schema::create('estoques', function (Blueprint $table) {
            $table->id();
            $table->string('item'); // Nome do produto
            $table->decimal('quantidade', 8, 2); // Quantidade atual
            $table->enum('unidade', ['litros', 'unidades', 'caixas']); // Unidade de medida
            $table->decimal('minimo_alerta', 8, 2); // Quantidade mínima para alerta
            $table->timestamps(); // Timestamps padrão
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estoques');
    }
};
