<?php

namespace App\Console\Commands;

use App\Casts\EncryptedWithHash;
use App\Models\Cadastro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando: Preencher hashes HMAC-SHA256 dos cadastros existentes.
 *
 * QUANDO EXECUTAR:
 * - Uma única vez após fazer deploy com as colunas *_hash criadas (migration 2026_02_28_130100)
 * - Novamente se a APP_KEY mudar (TODOS os hashes precisam ser recriados)
 *
 * ⚠️  IMPORTANTE: Se o campo já estiver criptografado com EncryptedWithHash, o método
 * get() do cast retorna o plaintext — portanto $cadastro->documento já retorna
 * o valor descriptografado, pronto para hashear.
 *
 * EXECUÇÃO:
 *   php artisan lgpd:populate-hashes           # Todos os cadastros
 *   php artisan lgpd:populate-hashes --dry-run  # Apenas simula, não salva
 *   php artisan lgpd:populate-hashes --chunk=500 # Tamanho do chunk (padrão: 200)
 *
 * ROLLBACK (se precisar resetar os hashes):
 *   UPDATE cadastros SET documento_hash = NULL, email_hash = NULL, telefone_hash = NULL, celular_hash = NULL;
 */
class PopulateEncryptedHashesCommand extends Command
{
    protected $signature = 'lgpd:populate-hashes
                            {--dry-run : Roda sem salvar}
                            {--chunk=200 : Quantidade de registros por lote}';

    protected $description = 'Preenche os hashes HMAC-SHA256 para busca segura de campos criptografados (documento, email, telefone, celular).';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        $this->info('🔒 LGPD — Preenchendo hashes de campos criptografados...');

        if ($isDryRun) {
            $this->warn('⚠️  Modo DRY-RUN ativo. Nenhum dado será salvo.');
        }

        $total = Cadastro::withTrashed()->count();
        $this->info("📊 Total de cadastros (incluindo deletados): {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processados = 0;
        $erros = 0;

        Cadastro::withTrashed()->orderBy('id')->chunk($chunk, function ($cadastros) use (&$processados, &$erros, $isDryRun, $bar) {
            foreach ($cadastros as $cadastro) {
                try {
                    $updates = [];

                    // Gera hash de cada campo se o valor não for nulo
                    // Nota: $cadastro->documento já retorna o plaintext (cast descriptografa ao ler)
                    if ($cadastro->documento !== null) {
                        $updates['documento_hash'] = EncryptedWithHash::makeHash($cadastro->documento);
                    }

                    if ($cadastro->email !== null) {
                        $updates['email_hash'] = EncryptedWithHash::makeHash($cadastro->email);
                    }

                    if ($cadastro->telefone !== null) {
                        $updates['telefone_hash'] = EncryptedWithHash::makeHash($cadastro->telefone);
                    }

                    if ($cadastro->celular !== null) {
                        $updates['celular_hash'] = EncryptedWithHash::makeHash($cadastro->celular);
                    }

                    if (!empty($updates) && !$isDryRun) {
                        // saveQuietly para não disparar observers nem events
                        DB::table('cadastros')
                            ->where('id', $cadastro->id)
                            ->update($updates);
                    }

                    $processados++;
                } catch (\Exception $e) {
                    $erros++;
                    $this->newLine();
                    $this->error("❌ Erro no cadastro ID {$cadastro->id}: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Concluído!");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Processados', $processados],
                ['Erros', $erros],
                ['Dry-run', $isDryRun ? 'Sim' : 'Não'],
            ]
        );

        if ($erros > 0) {
            $this->warn("⚠️  {$erros} registros com erro. Verifique os logs e tente novamente com --dry-run para investigar.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
