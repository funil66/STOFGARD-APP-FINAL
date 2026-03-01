<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create or ensure parent table exists
        if (! Schema::hasTable('orcamentos')) {
            Schema::create('orcamentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cadastro_id')->constrained('cadastros')->onDelete('cascade'); // Cliente
                $table->decimal('valor_total', 10, 2)->default(0);
                $table->string('status')->default('rascunho'); // rascunho, enviado, aprovado, rejeitado
                $table->date('data_validade')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unsignedBigInteger('vendedor_id')->nullable()->index();
                $table->unsignedBigInteger('loja_id')->nullable()->index();
                $table->decimal('comissao_vendedor', 10, 2)->nullable()->default(0);
                $table->decimal('comissao_loja', 10, 2)->nullable()->default(0);
            });
        }

        // Create child table if missing
        if (! Schema::hasTable('orcamento_itens')) {
            Schema::create('orcamento_itens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('orcamento_id')->constrained('orcamentos')->onDelete('cascade');

                // Here we store a snapshot: name and service type rather than a product id
                $table->string('item_nome');      // Ex: "Sofá Retrátil 3 Lugares"
                $table->string('servico_tipo');   // Ex: "impermeabilizacao", "higienizacao", "combo"
                $table->string('unidade')->default('un'); // Ex: "un", "m2"

                $table->decimal('quantidade', 10, 2)->default(1);
                $table->decimal('valor_unitario', 10, 2);
                $table->decimal('subtotal', 10, 2);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_itens');
        Schema::dropIfExists('orcamentos');
    }
};
