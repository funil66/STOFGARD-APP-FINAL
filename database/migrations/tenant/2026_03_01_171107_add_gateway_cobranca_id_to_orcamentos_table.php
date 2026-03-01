<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona campos de gateway de cobrança na tabela de orçamentos.
     * Permite rastrear cobranças geradas via Asaas/EFI por orçamento.
     */
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('gateway_cobranca_id')->nullable()->after('pix_txid')
                ->comment('ID da cobrança gerada no gateway (Asaas, EFI, etc.)');
            $table->string('status_pagamento')->nullable()->after('gateway_cobranca_id')
                ->comment('Status do pagamento: pendente, pago, expirado, cancelado');
            $table->timestamp('data_pagamento')->nullable()->after('status_pagamento')
                ->comment('Timestamp da confirmação de pagamento via webhook');
            $table->decimal('valor_pago', 10, 2)->nullable()->after('data_pagamento')
                ->comment('Valor efetivamente pago (pode diferir por taxas do gateway)');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_cobranca_id',
                'status_pagamento',
                'data_pagamento',
                'valor_pago',
            ]);
        });
    }
};
