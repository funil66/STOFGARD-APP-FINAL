<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ordens_servico')) {
            return;
        }

        Schema::table('ordens_servico', function (Blueprint $table) {
            if (! Schema::hasColumn('ordens_servico', 'assinatura')) {
                $table->longText('assinatura')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_metadata')) {
                $table->json('assinatura_metadata')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_pdf_hash')) {
                $table->string('assinatura_pdf_hash', 64)->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinado_em')) {
                $table->timestamp('assinado_em')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_ip')) {
                $table->string('assinatura_ip', 45)->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_user_agent')) {
                $table->text('assinatura_user_agent')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_timestamp')) {
                $table->timestamp('assinatura_timestamp')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_hash')) {
                $table->string('assinatura_hash', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ordens_servico')) {
            return;
        }

        $columnsToDrop = array_filter([
            Schema::hasColumn('ordens_servico', 'assinatura') ? 'assinatura' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_metadata') ? 'assinatura_metadata' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_pdf_hash') ? 'assinatura_pdf_hash' : null,
            Schema::hasColumn('ordens_servico', 'assinado_em') ? 'assinado_em' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_ip') ? 'assinatura_ip' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_user_agent') ? 'assinatura_user_agent' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_timestamp') ? 'assinatura_timestamp' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_hash') ? 'assinatura_hash' : null,
        ]);

        if (empty($columnsToDrop)) {
            return;
        }

        Schema::table('ordens_servico', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }
};
