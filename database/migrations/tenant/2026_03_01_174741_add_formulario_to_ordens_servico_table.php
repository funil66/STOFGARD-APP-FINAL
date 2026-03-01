<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->foreignId('formulario_id')
                ->nullable()
                ->after('extra_attributes')
                ->constrained('formularios_dinamicos')
                ->nullOnDelete();

            $table->json('respostas_formulario')
                ->nullable()
                ->after('formulario_id')
                ->comment('Respostas do formulário dinâmico em formato JSON');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropConstrainedForeignId('formulario_id');
            $table->dropColumn('respostas_formulario');
        });
    }
};
