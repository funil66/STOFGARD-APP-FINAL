<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adiciona metadata de assinatura digital com validade legal às Ordens de Serviço.
     *
     * FUNDAMENTO JURÍDICO:
     * Para validade como título executivo extrajudicial (art. 784, III, CPC),
     * a MP 2.200-2/2001, art. 10, §2º exige comprovação de autoria e integridade.
     * O hash SHA-256 + IP + User-Agent + timestamp garante essa rastreabilidade.
     */
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            // Metadata da assinatura eletrônica (JSON)
            $table->json('assinatura_metadata')->nullable()->after('assinatura');

            // Hash SHA-256 do PDF final no momento da assinatura
            $table->string('assinatura_pdf_hash', 64)->nullable()->after('assinatura_metadata');

            // Timestamp oficial da assinatura (com timezone)
            $table->timestamp('assinado_em')->nullable()->after('assinatura_pdf_hash');
        });

        // Mesma estrutura para Orçamentos (quando assinados)
        if (Schema::hasTable('orcamentos') && !Schema::hasColumn('orcamentos', 'assinatura_metadata')) {
            Schema::table('orcamentos', function (Blueprint $table) {
                $table->json('assinatura_metadata')->nullable()->after('etapa_funil');
                $table->string('assinatura_pdf_hash', 64)->nullable()->after('assinatura_metadata');
                $table->timestamp('assinado_em')->nullable()->after('assinatura_pdf_hash');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn(['assinatura_metadata', 'assinatura_pdf_hash', 'assinado_em']);
        });

        if (Schema::hasTable('orcamentos') && Schema::hasColumn('orcamentos', 'assinatura_metadata')) {
            Schema::table('orcamentos', function (Blueprint $table) {
                $table->dropColumn(['assinatura_metadata', 'assinatura_pdf_hash', 'assinado_em']);
            });
        }
    }
};
