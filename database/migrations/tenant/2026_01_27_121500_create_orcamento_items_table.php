<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_items', function (Blueprint $table) {
            $table->id();

            // Vínculo com o pai
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();

            // Dados do Item
            $table->string('item_nome');
            $table->string('servico_tipo'); // higienizacao, impermeabilizacao, etc
            $table->string('unidade')->default('un');
            $table->decimal('quantidade', 10, 2)->default(1);
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_items');
    }
};
