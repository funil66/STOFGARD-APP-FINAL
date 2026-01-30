<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Configuracao;
use Symfony\Component\Process\Process;

class IronCheck extends Command
{
    protected $signature = 'iron:check';
    protected $description = 'Verifica a saúde da infraestrutura e dependências (Iron Code)';

    public function handle()
    {
        $this->info("🛡️  INICIANDO PROTOCOLO IRON CODE...");
        $this->newLine();

        // 1. Banco de Dados
        $this->runCheck("Banco de Dados", function() {
             DB::connection()->getPdo();
        });

        // 2. Configuração
        $this->runCheck("Configuração de Clima (Widget)", function() {
            return Configuracao::where('chave', 'url_clima')->exists();
        });

        // 3. Node.js (PDF)
        $this->runCheck("Binário Node.js", function() {
            $node = config('app.node_binary', 'node');
            $process = new Process([$node, '-v']);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception("Comando '$node' falhou.");
            }
            $this->comment("      (Versão: " . trim($process->getOutput()) . ")");
            return true;
        });

        // 4. Puppeteer
        $this->runCheck("Pacote NPM Puppeteer", function() {
            if (!file_exists(base_path('node_modules/puppeteer'))) {
                throw new \Exception("Pasta node_modules/puppeteer não encontrada.");
            }
        });

        // 5. Google Drive (Backup)
        $this->runCheck("Driver Google Drive", function() {
            if (!class_exists(\Masbug\Flysystem\GoogleDriveAdapter::class) && 
                !class_exists(\Spatie\FlysystemGoogleDrive\GoogleDriveAdapter::class)) {
                $this->warn("      [ALERTA] Adaptador Google Drive ausente. Backup nuvem falhará.");
                return false; // Não quebra, apenas avisa
            }
        });

        $this->newLine();
        $this->info("🏁 DIAGNÓSTICO CONCLUÍDO.");
        return 0;
    }

    private function runCheck($label, $callback)
    {
        $this->output->write("   Running: $label ... ");
        try {
            $result = $callback();
            if ($result !== false) {
                $this->info("OK");
            } else {
                $this->warn("FALHOU (Não Crítico)");
            }
        } catch (\Exception $e) {
            $this->error("ERRO");
            $this->error("      -> " . $e->getMessage());
        }
    }
}