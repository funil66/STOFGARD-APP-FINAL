<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Adiciona tenant_id a TODAS as tabelas de negócio.
 *
 * ESTRATÉGIA ZERO-PERDA:
 * 1. Cria tenant "default" (id = 1) — representa dados legados
 * 2. Adiciona tenant_id NULLABLE em cada tabela
 * 3. Backfill: UPDATE todos os registros existentes para tenant_id = 1
 * 4. Torna tenant_id NOT NULL
 * 5. Cria índice para performance das queries com TenantScope
 *
 * ROLLBACK: Remove tenant_id de todas as tabelas (não apaga dados de tenant).
 * O tenant "default" permanece para eventual re-migrate.
 */
return new class extends Migration {
    /**
     * Tabelas de negócio que precisam de tenant_id.
     * A tabela `users` fica separada pois pode ter users globais (super admins).
     */
    private array $businessTables = [
        'cadastros',
        'orcamentos',
        'orcamento_items',
        'ordens_servico',
        'ordem_servico_items',
        'ordem_servico_estoques',
        'financeiros',
        'agendas',
        'categorias',
        'configuracoes',
        'garantias',
        'google_tokens',
        'lista_desejos',
        'nota_fiscals',
        'pdf_generations',
        'produtos',
        'estoques',
        'sequencias',
        'tabela_precos',
        'whatsapp_messages',
        'settings',
        'tarefas',
        'equipamentos',
        'movimentacoes_financeiras', // Adicionado por segurança
    ];

    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────
        // PASSO 1: Criar o tenant "default" para dados legados
        // ─────────────────────────────────────────────────────────────
        DB::table('tenants')->insertOrIgnore([
            'id' => 1,
            'name' => 'Stofgard (Padrão)',
            'slug' => 'default',
            'plan' => 'pro',
            'is_active' => true,
            'settings' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ─────────────────────────────────────────────────────────────
        // PASSO 2 + 3 + 4 + 5: Para cada tabela de negócio
        // ─────────────────────────────────────────────────────────────
        foreach ($this->businessTables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue; // Pula tabelas que podem não existir em alguns ambientes
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            });

            // Backfill: todos os registros existentes = tenant default
            DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => 1]);

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Restrição de NOT NULL após backfill
                $table->unsignedBigInteger('tenant_id')->nullable(false)->change();

                // Foreign key (sem CASCADE DELETE — tenant deletado não deve apagar dados de negócio)
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->restrictOnDelete();

                // Índice para performance das queries do TenantScope
                // Prefixo "ti_" (tenant index) para evitar conflito com índices existentes
                $table->index('tenant_id', "ti_{$tableName}");
            });
        }

        // Adiciona tenant_id em users (nullable = super admins não têm tenant)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
                $table->index('tenant_id', 'ti_users');
            }
        });

        // Backfill users (admins existentes = tenant default, clientes = NULL até associar)
        DB::table('users')
            ->where('is_admin', true)
            ->whereNull('tenant_id')
            ->update(['tenant_id' => 1]);
    }

    public function down(): void
    {
        foreach ($this->businessTables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex("ti_{$tableName}");
                $table->dropColumn('tenant_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex('ti_users');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
