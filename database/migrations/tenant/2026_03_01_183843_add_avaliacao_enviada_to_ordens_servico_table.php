<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Fase 4: Rastreamento de avaliações Google Meu Negócio enviadas.
     * Previne reenvio duplicado — usado pelo EnviarSolicitacaoAvaliacaoJob.
     */
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->boolean('avaliacao_enviada')
                ->default(false)
                ->after('respostas_formulario')
                ->comment('Flag para evitar reenvio do WhatsApp de avaliação GMB');

            $table->timestamp('avaliacao_enviada_em')
                ->nullable()
                ->after('avaliacao_enviada')
                ->comment('Timestamp do envio da solicitação de avaliação');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn(['avaliacao_enviada', 'avaliacao_enviada_em']);
        });
    }
};
