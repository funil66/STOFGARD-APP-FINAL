<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('avaliacoes')) {
            Schema::create('avaliacoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
                $table->foreignId('cadastro_id')->nullable()->constrained('cadastros')->nullOnDelete();
                $table->unsignedTinyInteger('nota')->nullable()->comment('0-10 NPS scale');
                $table->text('comentario')->nullable();
                $table->string('token', 64)->unique();
                $table->timestamp('respondida_em')->nullable();
                $table->timestamps();

                $table->index(['cadastro_id', 'nota']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
