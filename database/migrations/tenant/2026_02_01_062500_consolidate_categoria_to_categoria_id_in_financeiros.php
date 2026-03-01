<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Consolida o campo 'categoria' (string) para 'categoria_id' (FK).
     *
     * Estratégia:
     * 1. Criar registros faltantes na tabela categorias
     * 2. Atualizar financeiros.categoria_id baseado na string
     * 3. Remover coluna categoria (string)
     */
    public function up(): void
    {
        $table = null;
        if (Schema::hasTable('financeiros')) {
            $table = 'financeiros';
        } elseif (Schema::hasTable('transacoes_financeiras')) {
            $table = 'transacoes_financeiras';
        }

        if (!$table) {
            $this->log('Nenhuma tabela financeira encontrada; pulando migração.');

            return;
        }

        if (!Schema::hasColumn($table, 'categoria')) {
            $this->log('Coluna categoria não existe; consolidação já concluída.');
            return;
        }


        // PASSO 1: Coletar todas as categorias únicas como string
        $categoriasString = DB::table($table)
            ->whereNotNull('categoria')
            ->whereNull('categoria_id')
            ->distinct()
            ->pluck('categoria');

        $this->log("📋 Encontradas {$categoriasString->count()} categorias únicas como string");

        // PASSO 2: Criar registros na tabela categorias se não existirem
        $criadasCount = 0;
        foreach ($categoriasString as $nomeCategoria) {
            if (empty($nomeCategoria)) {
                continue;
            }

            $slug = Str::slug($nomeCategoria);

            // Verificar se já existe
            $exists = DB::table('categorias')
                ->where('slug', $slug)
                ->orWhere('nome', $nomeCategoria)
                ->exists();

            if (!$exists) {
                DB::table('categorias')->insert([
                    'nome' => $nomeCategoria,
                    'slug' => $slug,
                    'tipo' => $this->inferirTipoCategoria($nomeCategoria),
                    'sistema' => 'financeiro',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $criadasCount++;
                $this->log("  ✓ Criada categoria: {$nomeCategoria}");
            }
        }

        $this->log("✅ {$criadasCount} novas categorias criadas");

        // PASSO 3: Atualizar categoria_id para registros com categoria string
        $financeiros = DB::table($table)
            ->whereNotNull('categoria')
            ->whereNull('categoria_id')
            ->get();

        $atualizados = 0;
        foreach ($financeiros as $financeiro) {
            $categoria = DB::table('categorias')
                ->where('nome', $financeiro->categoria)
                ->orWhere('slug', Str::slug($financeiro->categoria))
                ->first();

            if ($categoria) {
                DB::table('financeiros')
                    ->where('id', $financeiro->id)
                    ->update(['categoria_id' => $categoria->id]);
                $atualizados++;
            }
        }

        $this->log("✅ {$atualizados} registros financeiros atualizados com categoria_id");

        // PASSO 4: Remover coluna categoria (string)
        if (Schema::hasColumn($table, 'categoria')) {
            // Em SQLite, DROP COLUMN pode falhar se houver views dependentes — optar por pular
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table($table, function (Blueprint $tbl) {
                    $tbl->dropIndex(['categoria']); // remover índice se existir
                });

                Schema::table($table, function (Blueprint $tbl) {
                    $tbl->dropColumn('categoria');
                });

                $this->log('✅ Coluna categoria (string) removida');
            } else {
                $this->log('⚠ DRIVER sqlite detectado — remoção da coluna categoria pulada (incompatibilidade com views).');
            }
        }

        $this->log("\n🎉 CONSOLIDAÇÃO DE CATEGORIAS CONCLUÍDA!");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recriar coluna categoria como string (na tabela financeira existente)
        $table = null;
        if (Schema::hasTable('financeiros')) {
            $table = 'financeiros';
        } elseif (Schema::hasTable('transacoes_financeiras')) {
            $table = 'transacoes_financeiras';
        }

        if (!$table) {
            $this->log('Nenhuma tabela financeira encontrada; pulando rollback.');

            return;
        }

        Schema::table($table, function (Blueprint $tbl) {
            $tbl->string('categoria')->nullable()->after('descricao');
        });

        // Copiar dados de categoria_id de volta para string
        $financeiros = DB::table($table)
            ->whereNotNull('categoria_id')
            ->get();

        foreach ($financeiros as $financeiro) {
            $categoria = DB::table('categorias')
                ->where('id', $financeiro->categoria_id)
                ->first();

            if ($categoria) {
                DB::table('financeiros')
                    ->where('id', $financeiro->id)
                    ->update(['categoria' => $categoria->nome]);
            }
        }

        Schema::table('financeiros', function (Blueprint $table) {
            $table->index('categoria');
        });
    }

    /**
     * Infere o tipo de categoria baseado no nome.
     */
    private function inferirTipoCategoria(string $nome): string
    {
        $nome = strtolower($nome);

        $receitas = ['servico', 'venda', 'comissao', 'receita', 'pagamento'];
        $despesas = ['material', 'despesa', 'custo', 'fornecedor', 'compra'];

        foreach ($receitas as $keyword) {
            if (str_contains($nome, $keyword)) {
                return 'financeiro_receita';
            }
        }

        foreach ($despesas as $keyword) {
            if (str_contains($nome, $keyword)) {
                return 'financeiro_despesa';
            }
        }

        return 'financeiro_receita'; // padrão
    }

    /**
     * Helper para log no console.
     */
    private function log(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
    }
};
