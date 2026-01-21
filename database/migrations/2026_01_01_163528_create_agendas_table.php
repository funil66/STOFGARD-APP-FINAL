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
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();

            // Data e hora
            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim');
            $table->boolean('dia_inteiro')->default(false);

            // Tipo e Status
            $table->enum('tipo', ['visita', 'servico', 'follow_up', 'reuniao', 'outro']);
            $table->enum('status', ['agendado', 'confirmado', 'em_andamento', 'concluido', 'cancelado'])->default('agendado');

            // Relacionamentos
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('cascade');
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->onDelete('set null');
            $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->onDelete('set null');

            // Localização
            $table->string('local')->nullable();
            $table->text('endereco_completo')->nullable();

            // Notificações e lembretes
            $table->boolean('lembrete_enviado')->default(false);
            $table->integer('minutos_antes_lembrete')->default(60); // 1 hora antes

            // Cor para visualização no calendário
            $table->string('cor', 7)->default('#3b82f6'); // Azul padrão

            // Observações e auditoria
            $table->text('observacoes')->nullable();
            $table->string('criado_por');
            $table->string('atualizado_por')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('data_hora_inicio');
            $table->index('data_hora_fim');
            $table->index('tipo');
            $table->index('status');
            $table->index('cliente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
