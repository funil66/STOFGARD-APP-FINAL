<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adiciona campos de gateway de pagamento na tabela configuracoes do tenant.
     * Roda no banco isolado do tenant.
     */
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            // Gateway do Tenant para cobrar seus clientes
            $table->string('gateway_provider')->nullable()->after('config_prazo_garantia')
                ->comment('Provedor: asaas, efipay, pagseguro, mercadopago');
            $table->text('gateway_token_encrypted')->nullable()->after('gateway_provider')
                ->comment('Token/API Key do tenant (armazenado encriptado)');
            $table->string('gateway_webhook_token')->nullable()->after('gateway_token_encrypted')
                ->comment('UUID único para identificar este tenant nos webhooks PIX');
            $table->string('pix_chave')->nullable()->after('gateway_webhook_token')
                ->comment('Chave PIX do autônomo (fallback manual se não usar gateway)');
            $table->string('pix_tipo_chave')->nullable()->after('pix_chave')
                ->comment('Tipo da chave PIX: cpf, cnpj, email, telefone, aleatoria');

            // Configurações de marketing
            $table->string('gmb_link')->nullable()->after('pix_tipo_chave')
                ->comment('Link direto para avaliação Google Meu Negócio');
            $table->text('mensagem_avaliacao')->nullable()->after('gmb_link')
                ->comment('Template da mensagem de solicitação de avaliação');
            $table->boolean('habilitar_avaliacao_automatica')->default(false)->after('mensagem_avaliacao');

            // White-label (Tier Elite)
            $table->string('cor_primaria_cliente')->nullable()->after('habilitar_avaliacao_automatica')
                ->comment('Cor primária do portal do cliente (hex)');
            $table->string('logo_cliente_path')->nullable()->after('cor_primaria_cliente')
                ->comment('Path da logo no storage para o portal do cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_provider',
                'gateway_token_encrypted',
                'gateway_webhook_token',
                'pix_chave',
                'pix_tipo_chave',
                'gmb_link',
                'mensagem_avaliacao',
                'habilitar_avaliacao_automatica',
                'cor_primaria_cliente',
                'logo_cliente_path',
            ]);
        });
    }
};
