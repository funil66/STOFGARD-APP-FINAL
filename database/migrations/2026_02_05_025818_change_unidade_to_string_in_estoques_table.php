<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpeza preventiva
        \Illuminate\Support\Facades\DB::statement("DROP VIEW IF EXISTS financeiro_audit");
        \Illuminate\Support\Facades\DB::statement("DROP TABLE IF EXISTS __temp__estoques");
        Schema::dropIfExists('estoques_new');

        // 1. Criar nova tabela com estrutura correta (unidade como string)
        Schema::create('estoques_new', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('item');
            $table->decimal('quantidade', 10, 2)->default(0);
            $table->string('unidade')->default('unidade'); // Alterado para string simples
            $table->decimal('minimo_alerta', 10, 2)->default(5);
            $table->string('tipo')->default('geral');
            $table->text('observacoes')->nullable();
        });

        // 2. Copiar dados (SE a tabela antiga existir)
        if (Schema::hasTable('estoques')) {
            // Apenas copia as colunas que realmente existem na tabela
            $colunas = implode(',', ['id', 'created_at', 'updated_at', 'item', 'quantidade', 'unidade', 'minimo_alerta']);
            \Illuminate\Support\Facades\DB::statement("INSERT INTO estoques_new ($colunas) SELECT $colunas FROM estoques");

            // 3. Dropar tabela antiga
            Schema::drop('estoques');
        }

        // 4. Renomear nova tabela
        Schema::rename('estoques_new', 'estoques');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recria com enum se precisar reverter
        Schema::create('estoques_new', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('item'); // Nome do produto
            $table->decimal('quantidade', 10, 2)->default(0); // Quantidade atual
            $table->enum('unidade', ['unidade', 'litros', 'caixa', 'metro'])->default('unidade'); // Unidade de medida
            $table->decimal('minimo_alerta', 10, 2)->default(5); // Quantidade mínima para alerta
            $table->string('tipo')->default('geral'); // Tipo do item (químico ou geral)
            $table->text('observacoes')->nullable(); // Observações adicionais
        });

        $colunas = implode(',', ['id', 'created_at', 'updated_at', 'item', 'quantidade', 'unidade', 'minimo_alerta', 'tipo', 'observacoes']);
        // Cuidado com valores que não cabem no enum ao reverter
        \Illuminate\Support\Facades\DB::statement("INSERT INTO estoques_new ($colunas) SELECT $colunas FROM estoques WHERE unidade IN ('unidade', 'litros', 'caixa', 'metro')");

        Schema::drop('estoques');
        Schema::rename('estoques_new', 'estoques');
    }
};
