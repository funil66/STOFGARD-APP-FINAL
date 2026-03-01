<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Agendamentos feitos pelo cliente final via página pública.
     * Race condition prevenida pela coluna `reservado_ate`.
     */
    public function up(): void
    {
        Schema::create('agendamentos_publicos', function (Blueprint $table) {
            $table->id();

            // Dados do slot de tempo
            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim');
            $table->integer('duracao_minutos')->default(60);

            // Status do agendamento
            $table->enum('status', [
                'reservado',    // PIX ainda não pago (slot travado por reservado_ate)
                'confirmado',   // PIX pago — slot garantido
                'cancelado',    // Cancelado (manualmente ou timeout)
                'concluido',    // Serviço realizado
            ])->default('reservado');

            // Dados do cliente final (sem login)
            $table->string('cliente_nome');
            $table->string('cliente_telefone');
            $table->string('cliente_email')->nullable();
            $table->text('cliente_observacao')->nullable();

            // Sinal de pagamento (PIX)
            $table->decimal('valor_sinal', 10, 2)->nullable();
            $table->string('gateway_cobranca_id')->nullable(); // ID no Asaas/EFI
            $table->string('pix_copia_cola')->nullable();
            $table->timestamp('pix_expira_em')->nullable();

            // Prevenção de race condition: slot travado temporariamente
            $table->timestamp('reservado_ate')->nullable()
                ->comment('Slot liberado se PIX não for pago até este timestamp');

            // Link com a Agenda do tenant (criada ao confirmar)
            $table->foreignId('agenda_id')->nullable()->constrained('agendas')->nullOnDelete();

            // Tipo de serviço desejado
            $table->string('tipo_servico')->nullable();
            $table->string('token_confirmacao')->unique()
                ->comment('UUID enviado no WhatsApp de confirmação');

            $table->timestamps();
            $table->softDeletes();

            // Index para buscas de slots disponíveis
            $table->index(['data_hora_inicio', 'status']);
            $table->index('reservado_ate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos_publicos');
    }
};
