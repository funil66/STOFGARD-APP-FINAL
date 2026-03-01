<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Blindando Ordens de Serviço
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('assinatura_ip', 45)->nullable()->after('status');
            $table->text('assinatura_user_agent')->nullable()->after('assinatura_ip');
            $table->timestamp('assinatura_timestamp')->nullable()->after('assinatura_user_agent');
            $table->string('assinatura_hash', 64)->nullable()->comment('Hash SHA-256 da transação')->after('assinatura_timestamp');
        });

        // Blindando Orçamentos
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('assinatura_ip', 45)->nullable()->after('status');
            $table->text('assinatura_user_agent')->nullable()->after('assinatura_ip');
            $table->timestamp('assinatura_timestamp')->nullable()->after('assinatura_user_agent');
            $table->string('assinatura_hash', 64)->nullable()->comment('Hash SHA-256 da transação')->after('assinatura_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn(['assinatura_ip', 'assinatura_user_agent', 'assinatura_timestamp', 'assinatura_hash']);
        });

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['assinatura_ip', 'assinatura_user_agent', 'assinatura_timestamp', 'assinatura_hash']);
        });
    }
};
