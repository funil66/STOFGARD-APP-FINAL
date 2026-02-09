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
        if (Schema::hasTable('agendas')) {
            return;
        }

        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim')->nullable();
            $table->string('tipo_evento')->default('compromisso'); // compromisso, servico, lembrete, nota
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('local')->nullable();
            $table->boolean('dia_inteiro')->default(false);
            $table->boolean('e_lembrete')->default(false);
            $table->boolean('lembrete_concluido')->default(false);
            $table->string('cor', 7)->default('#3b82f6'); // Cor do evento no calendário
            $table->string('google_calendar_id')->nullable()->unique(); // ID do evento no Google Calendar
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('criado_por', 10)->nullable(); // Iniciais do usuário
            $table->timestamps();

            // Índices para melhor performance
            $table->index('data_inicio');
            $table->index('tipo_evento');
            $table->index('e_lembrete');
            $table->index('user_id');
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
