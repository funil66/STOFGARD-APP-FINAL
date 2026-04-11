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
            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico')) {
                $table->longText('assinatura_tecnico')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_metadata')) {
                $table->json('assinatura_tecnico_metadata')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_pdf_hash')) {
                $table->string('assinatura_tecnico_pdf_hash', 64)->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinado_tecnico_em')) {
                $table->timestamp('assinado_tecnico_em')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_ip')) {
                $table->string('assinatura_tecnico_ip', 45)->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_user_agent')) {
                $table->text('assinatura_tecnico_user_agent')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_timestamp')) {
                $table->timestamp('assinatura_tecnico_timestamp')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_hash')) {
                $table->string('assinatura_tecnico_hash', 64)->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_user_id')) {
                $table->unsignedBigInteger('assinatura_tecnico_user_id')->nullable();
            }

            if (! Schema::hasColumn('ordens_servico', 'assinatura_tecnico_user_name')) {
                $table->string('assinatura_tecnico_user_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ordens_servico')) {
            return;
        }

        $columnsToDrop = array_filter([
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico') ? 'assinatura_tecnico' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_metadata') ? 'assinatura_tecnico_metadata' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_pdf_hash') ? 'assinatura_tecnico_pdf_hash' : null,
            Schema::hasColumn('ordens_servico', 'assinado_tecnico_em') ? 'assinado_tecnico_em' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_ip') ? 'assinatura_tecnico_ip' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_user_agent') ? 'assinatura_tecnico_user_agent' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_timestamp') ? 'assinatura_tecnico_timestamp' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_hash') ? 'assinatura_tecnico_hash' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_user_id') ? 'assinatura_tecnico_user_id' : null,
            Schema::hasColumn('ordens_servico', 'assinatura_tecnico_user_name') ? 'assinatura_tecnico_user_name' : null,
        ]);

        if (empty($columnsToDrop)) {
            return;
        }

        Schema::table('ordens_servico', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }
};
