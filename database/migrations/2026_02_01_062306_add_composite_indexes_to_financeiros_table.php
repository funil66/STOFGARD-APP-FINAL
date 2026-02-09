<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona índices compostos para otimizar queries frequentes no sistema financeiro.
     */
    public function up(): void
    {
        if (Schema::hasTable('financeiros')) {
            Schema::table('financeiros', function (Blueprint $table) {
                if (Schema::hasColumn('financeiros', 'cadastro_id') && Schema::hasColumn('financeiros', 'status') && Schema::hasColumn('financeiros', 'tipo')) {
                    $table->index(['cadastro_id', 'status', 'tipo'], 'idx_financeiros_cadastro_status_tipo');
                }

                if (Schema::hasColumn('financeiros', 'data_vencimento') && Schema::hasColumn('financeiros', 'status')) {
                    $table->index(['data_vencimento', 'status'], 'idx_financeiros_vencimento_status');
                }

                if (Schema::hasColumn('financeiros', 'ordem_servico_id') && Schema::hasColumn('financeiros', 'tipo')) {
                    $table->index(['ordem_servico_id', 'tipo'], 'idx_financeiros_os_tipo');
                }

                if (Schema::hasColumn('financeiros', 'pix_status') && Schema::hasColumn('financeiros', 'pix_expiracao')) {
                    $table->index(['pix_status', 'pix_expiracao'], 'idx_financeiros_pix_status');
                }
            });
        } elseif (Schema::hasTable('transacoes_financeiras')) {
            Schema::table('transacoes_financeiras', function (Blueprint $table) {
                if (Schema::hasColumn('transacoes_financeiras', 'cadastro_id') && Schema::hasColumn('transacoes_financeiras', 'status') && Schema::hasColumn('transacoes_financeiras', 'tipo')) {
                    $table->index(['cadastro_id', 'status', 'tipo'], 'idx_financeiros_cadastro_status_tipo');
                }

                if (Schema::hasColumn('transacoes_financeiras', 'data_vencimento') && Schema::hasColumn('transacoes_financeiras', 'status')) {
                    $table->index(['data_vencimento', 'status'], 'idx_financeiros_vencimento_status');
                }

                if (Schema::hasColumn('transacoes_financeiras', 'ordem_servico_id') && Schema::hasColumn('transacoes_financeiras', 'tipo')) {
                    $table->index(['ordem_servico_id', 'tipo'], 'idx_financeiros_os_tipo');
                }

                if (Schema::hasColumn('transacoes_financeiras', 'pix_status') && Schema::hasColumn('transacoes_financeiras', 'pix_expiracao')) {
                    $table->index(['pix_status', 'pix_expiracao'], 'idx_financeiros_pix_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('financeiros')) {
            Schema::table('financeiros', function (Blueprint $table) {
                if (Schema::hasColumn('financeiros', 'cadastro_id') && Schema::hasColumn('financeiros', 'status') && Schema::hasColumn('financeiros', 'tipo')) {
                    $table->dropIndex('idx_financeiros_cadastro_status_tipo');
                }

                if (Schema::hasColumn('financeiros', 'data_vencimento') && Schema::hasColumn('financeiros', 'status')) {
                    $table->dropIndex('idx_financeiros_vencimento_status');
                }

                if (Schema::hasColumn('financeiros', 'ordem_servico_id') && Schema::hasColumn('financeiros', 'tipo')) {
                    $table->dropIndex('idx_financeiros_os_tipo');
                }

                if (Schema::hasColumn('financeiros', 'pix_status') && Schema::hasColumn('financeiros', 'pix_expiracao')) {
                    $table->dropIndex('idx_financeiros_pix_status');
                }
            });
        } elseif (Schema::hasTable('transacoes_financeiras')) {
            Schema::table('transacoes_financeiras', function (Blueprint $table) {
                if (Schema::hasColumn('transacoes_financeiras', 'cadastro_id') && Schema::hasColumn('transacoes_financeiras', 'status') && Schema::hasColumn('transacoes_financeiras', 'tipo')) {
                    $table->dropIndex('idx_financeiros_cadastro_status_tipo');
                }

                if (Schema::hasColumn('transacoes_financeiras', 'data_vencimento') && Schema::hasColumn('transacoes_financeiras', 'status')) {
                    $table->dropIndex('idx_financeiros_vencimento_status');
                }

                if (Schema::hasColumn('transacoes_financeiras', 'ordem_servico_id') && Schema::hasColumn('transacoes_financeiras', 'tipo')) {
                    $table->dropIndex('idx_financeiros_os_tipo');
                }

                if (Schema::hasColumn('transacoes_financeiras', 'pix_status') && Schema::hasColumn('transacoes_financeiras', 'pix_expiracao')) {
                    $table->dropIndex('idx_financeiros_pix_status');
                }
            });
        }
    }
};
