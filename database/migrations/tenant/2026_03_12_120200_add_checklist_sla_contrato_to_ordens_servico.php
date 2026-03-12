<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->json('checklist_itens')->nullable()->after('respostas_formulario')
                ->comment('JSON: [{titulo, concluido, foto_path, observacao, concluido_em}]');
            $table->unsignedInteger('prazo_sla_horas')->nullable()->after('data_prevista')
                ->comment('SLA em horas úteis');
            $table->timestamp('sla_alerta_enviado_em')->nullable()->after('prazo_sla_horas');
            $table->foreignId('contrato_servico_id')->nullable()->after('orcamento_id')
                ->constrained('contratos_servico')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contrato_servico_id');
            $table->dropColumn(['checklist_itens', 'prazo_sla_horas', 'sla_alerta_enviado_em']);
        });
    }
};
