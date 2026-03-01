<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adiciona campos de billing/assinatura na tabela principal de tenants (landlord DB).
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Gateway do Super Admin (Asaas) para cobrar o tenant
            $table->string('gateway_customer_id')->nullable()->after('plan')
                ->comment('ID do cliente no Asaas (Super Admin)');
            $table->string('gateway_subscription_id')->nullable()->after('gateway_customer_id')
                ->comment('ID da assinatura ativa no Asaas');

            // Status do pagamento da assinatura SaaS
            $table->enum('status_pagamento', ['trial', 'ativo', 'inadimplente', 'suspenso', 'cancelado'])
                ->default('trial')
                ->after('gateway_subscription_id');

            // Datas de controle
            $table->date('data_vencimento')->nullable()->after('status_pagamento')
                ->comment('Vencimento da próxima fatura');
            $table->date('trial_termina_em')->nullable()->after('data_vencimento')
                ->comment('Data de término do período trial');

            // Limites do plano (já existe max_users e max_orcamentos_mes, adicionando OS)
            $table->integer('limite_os_mes')->default(30)->after('max_orcamentos_mes')
                ->comment('Limite mensal de Ordens de Serviço');
            $table->integer('os_criadas_mes_atual')->default(0)->after('limite_os_mes')
                ->comment('Contador de OS no mês corrente (resetado todo mês)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_customer_id',
                'gateway_subscription_id',
                'status_pagamento',
                'data_vencimento',
                'trial_termina_em',
                'limite_os_mes',
                'os_criadas_mes_atual',
            ]);
        });
    }
};
