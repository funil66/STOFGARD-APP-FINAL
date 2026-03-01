<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona colunas de hash SHA-256 para campos sensíveis criptografados.
     *
     * ESTRATÉGIA:
     * Campos como CPF/CNPJ e telefone serão criptografados com AES-256 (Laravel encrypted cast).
     * Colunas de hash (SHA-256 com HMAC + salt) permitem busca exata sem expor os dados.
     *
     * FUNDAMENTO JURÍDICO:
     * LGPD, art. 46: medidas técnicas aptas a proteger dados pessoais de acessos não autorizados.
     * Em caso de vazamento de banco, dados cifrados configuram diligência técnica
     * e reduzem culpabilidade na avaliação da ANPD (multa art. 52: até 2% do faturamento).
     */
    public function up(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            // Hashes para busca exata sem expor o dado cifrado
            $table->string('documento_hash', 64)->nullable()->after('documento')
                ->comment('SHA-256 HMAC do CPF/CNPJ — busca exata sem expor o dado original');

            $table->string('telefone_hash', 64)->nullable()->after('telefone')
                ->comment('SHA-256 HMAC do telefone — busca exata');

            $table->string('celular_hash', 64)->nullable()->after('celular')
                ->comment('SHA-256 HMAC do celular — busca exata');

            $table->string('email_hash', 64)->nullable()->after('email')
                ->comment('SHA-256 HMAC do e-mail — busca exata');

            // Índices para buscas por hash (performance)
            $table->index('documento_hash');
            $table->index('email_hash');
        });
    }

    public function down(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            $table->dropIndex(['documento_hash']);
            $table->dropIndex(['email_hash']);
            $table->dropColumn(['documento_hash', 'telefone_hash', 'celular_hash', 'email_hash']);
        });
    }
};
