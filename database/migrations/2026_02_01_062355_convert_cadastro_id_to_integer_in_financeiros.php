<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * CRÍTICO: Converte cadastro_id de string ("cliente_123") para integer FK.
     *
     * Estratégia:
     * 1. Criar coluna temporária cadastro_id_new (integer)
     * 2. Migrar dados legados de cliente_id/parceiro_id
     * 3. Converter strings "cliente_X" e "parceiro_X" para IDs de Cadastro
     * 4. Substituir coluna antiga pela nova
     * 5. Criar FK para tabela cadastros
     *
     * ATENÇÃO: Requer backup completo antes de executar!
     */
    public function up(): void
    {
        if (!Schema::hasTable('financeiros')) {
            $this->log('Tabela "financeiros" não existe; pulando migração.');

            return;
        }

        DB::transaction(function () {
            // PASSO 1: Criar coluna temporária
            Schema::table('financeiros', function (Blueprint $table) {
                $table->unsignedBigInteger('cadastro_id_new')->nullable()->after('id');
            });

            $this->log('✓ Coluna temporária cadastro_id_new criada');

            // PASSO 2: Migrar dados de cliente_id legado
            $clientesMigrados = DB::table('financeiros')
                ->whereNotNull('cliente_id')
                ->whereNull('cadastro_id_new')
                ->update([
                    'cadastro_id_new' => DB::raw('cliente_id'),
                ]);

            $this->log("✓ Migrados {$clientesMigrados} registros de cliente_id");

            // PASSO 3: Converter strings "cliente_123" → ID do Cadastro
            $financeiroComString = DB::table('financeiros')
                ->where(function ($query) {
                    $query->where('cadastro_id', 'LIKE', 'cliente_%')
                        ->orWhere('cadastro_id', 'LIKE', 'parceiro_%')
                        ->orWhere('cadastro_id', 'LIKE', 'loja_%')
                        ->orWhere('cadastro_id', 'LIKE', 'vendedor_%');
                })
                ->whereNull('cadastro_id_new')
                ->get();

            $convertidos = 0;
            $erros = 0;

            foreach ($financeiroComString as $row) {
                $cadastroId = $this->parseCadastroId($row->cadastro_id);

                if ($cadastroId) {
                    DB::table('financeiros')
                        ->where('id', $row->id)
                        ->update(['cadastro_id_new' => $cadastroId]);
                    $convertidos++;
                } else {
                    $this->log("⚠ Não foi possível converter cadastro_id: {$row->cadastro_id} (ID: {$row->id})");
                    $erros++;
                }
            }

            $this->log("✓ Convertidos {$convertidos} registros com cadastro_id string");
            if ($erros > 0) {
                $this->log("⚠ {$erros} registros não puderam ser convertidos (ficarão NULL)");
            }

            // PASSO 4: Copiar valores numéricos diretos (se houver)
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                // SQLite não possui REGEXP por padrão — usar GLOB como fallback e CAST para INTEGER
                $numericos = DB::table('financeiros')
                    ->whereRaw("cadastro_id GLOB '[0-9]*'")
                    ->whereNull('cadastro_id_new')
                    ->update([
                        'cadastro_id_new' => DB::raw('CAST(cadastro_id AS INTEGER)'),
                    ]);
            } else {
                $numericos = DB::table('financeiros')
                    ->whereRaw('cadastro_id REGEXP \'^[0-9]+$\'')
                    ->whereNull('cadastro_id_new')
                    ->update([
                        'cadastro_id_new' => DB::raw('CAST(cadastro_id AS UNSIGNED)'),
                    ]);
            }

            if ($numericos > 0) {
                $this->log("✓ Migrados {$numericos} registros com cadastro_id numérico direto");
            }

            // PASSO 5: Verificar integridade
            $total = DB::table('financeiros')->count();
            $migrados = DB::table('financeiros')->whereNotNull('cadastro_id_new')->count();
            $pendentes = $total - $migrados;

            $this->log("\n📊 RESUMO DA MIGRAÇÃO:");
            $this->log("   Total de registros: {$total}");
            $this->log("   Migrados com sucesso: {$migrados}");
            $this->log("   Pendentes (NULL): {$pendentes}");

            // PASSO 6: Remover coluna antiga e renomear nova
            // Precisamos dropar view de auditoria temporariamente para permitir alterações em SQLite
            DB::statement('DROP VIEW IF EXISTS financeiro_audit');

            // Em SQLite precisamos remover índices que referenciam a coluna antes de dropar
            if (Schema::hasTable('financeiros')) {
                DB::statement('DROP INDEX IF EXISTS idx_financeiros_cadastro_status_tipo');
            }

            Schema::table('financeiros', function (Blueprint $table) {
                // Drop foreign key se existir
                try {
                    $table->dropForeign(['cadastro_id']);
                } catch (\Exception $e) {
                    // Ignora se não existir
                }

                $table->dropColumn('cadastro_id');
            });

            Schema::table('financeiros', function (Blueprint $table) {
                $table->renameColumn('cadastro_id_new', 'cadastro_id');
            });

            $this->log('✓ Coluna cadastro_id substituída por integer');

            // PASSO 7: Criar foreign key
            Schema::table('financeiros', function (Blueprint $table) {
                $table->foreign('cadastro_id')
                    ->references('id')
                    ->on('cadastros')
                    ->nullOnDelete();
            });

            $this->log('✓ Foreign key criada para tabela cadastros');

            // PASSO 8: Remover colunas legadas cliente_id e parceiro_id
            Schema::table('financeiros', function (Blueprint $table) {
                if (Schema::hasColumn('financeiros', 'cliente_id')) {
                    $table->dropColumn('cliente_id');
                }
                if (Schema::hasColumn('financeiros', 'parceiro_id')) {
                    $table->dropColumn('parceiro_id');
                }
            });

            $this->log('✓ Colunas legadas cliente_id e parceiro_id removidas');

            // RECRIAR view de auditoria (se aplicável) — mesmo SQL da migração de criação de view
            $selects = [];

            if (Schema::hasTable('financeiros')) {
                $selects[] = "SELECT 
                'financeiros' AS tabela,
                COUNT(*) AS total_registros,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) AS pagos,
                SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) AS total_entradas,
                SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) AS total_saidas,
                MAX(created_at) AS ultimo_registro
            FROM financeiros
            WHERE deleted_at IS NULL";
            }

            if (Schema::hasTable('transacoes_financeiras')) {
                $selects[] = "SELECT 
                'transacoes_financeiras' AS tabela,
                COUNT(*) AS total_registros,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) AS pagos,
                SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) AS total_entradas,
                SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS total_saidas,
                MAX(created_at) AS ultimo_registro
            FROM transacoes_financeiras
            WHERE deleted_at IS NULL";
            }

            if (!empty($selects)) {
                $sql = 'CREATE VIEW financeiro_audit AS ' . implode("\nUNION ALL\n", $selects);
                DB::statement($sql);
            }

            $this->log("\n✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!");
        });
    }

    /**
     * Reverse the migrations.
     *
     * ATENÇÃO: O rollback NÃO recupera os dados originais.
     * Use apenas em ambiente de desenvolvimento.
     */
    public function down(): void
    {
        if (!Schema::hasTable('financeiros')) {
            $this->log('Tabela "financeiros" não existe; pulando rollback.');

            return;
        }

        Schema::table('financeiros', function (Blueprint $table) {
            // Remover FK
            $table->dropForeign(['cadastro_id']);

            // Recriar coluna como string
            $table->string('cadastro_id_old', 50)->nullable();

            // Recriar colunas legadas
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('parceiro_id')->nullable();
        });

        // Converter de volta para string (valores ficarão como números)
        DB::table('financeiros')
            ->whereNotNull('cadastro_id')
            ->update(['cadastro_id_old' => DB::raw('CAST(cadastro_id AS CHAR)')]);

        Schema::table('financeiros', function (Blueprint $table) {
            $table->dropColumn('cadastro_id');
            $table->renameColumn('cadastro_id_old', 'cadastro_id');
        });
    }

    /**
     * Parseia cadastro_id no formato "tipo_id" e retorna o ID do Cadastro correspondente.
     *
     * @param  string  $cadastroId  Ex: "cliente_123", "parceiro_456"
     * @return int|null ID do cadastro na tabela unificada ou null se não encontrado
     */
    private function parseCadastroId(string $cadastroId): ?int
    {
        if (!str_contains($cadastroId, '_')) {
            return null;
        }

        [$tipo, $legacyId] = explode('_', $cadastroId, 2);

        if (!is_numeric($legacyId)) {
            return null;
        }

        $legacyId = (int) $legacyId;

        // Mapear tipos antigos para novos
        $tipoMap = [
            'cliente' => 'cliente',
            'parceiro' => 'parceiro',
            'loja' => 'loja',
            'vendedor' => 'vendedor',
        ];

        if (!isset($tipoMap[$tipo])) {
            return null;
        }

        // Buscar na tabela cadastros
        // Estratégia 1: Procurar por legacy_cliente_id ou legacy_parceiro_id
        $cadastro = DB::table('cadastros')
            ->where('tipo', $tipoMap[$tipo])
            ->where(function ($query) use ($tipo, $legacyId) {
                if ($tipo === 'cliente') {
                    $query->where('legacy_cliente_id', $legacyId)
                        ->orWhere('id', $legacyId); // fallback
                } elseif ($tipo === 'parceiro') {
                    $query->where('legacy_parceiro_id', $legacyId)
                        ->orWhere('id', $legacyId); // fallback
                } else {
                    $query->where('id', $legacyId);
                }
            })
            ->first();

        return $cadastro ? $cadastro->id : null;
    }

    /**
     * Helper para log no console durante migração.
     */
    private function log(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
    }
};
