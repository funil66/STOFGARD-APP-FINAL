<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos_servico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cadastro_id')->constrained('cadastros')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('tipo_servico')->nullable();
            $table->string('frequencia')->default('mensal')->comment('mensal, bimestral, trimestral, semestral, anual');
            $table->decimal('valor', 12, 2)->default(0);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->date('proximo_agendamento')->nullable();
            $table->unsignedInteger('dia_vencimento')->default(10);
            $table->string('status')->default('ativo')->comment('ativo, pausado, cancelado, encerrado');
            $table->boolean('gerar_os_automatica')->default(true);
            $table->boolean('gerar_financeiro_automatico')->default(true);
            $table->text('observacoes')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'proximo_agendamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos_servico');
    }
};
