<?php

namespace App\Console\Commands;

use App\Services\PixService;
use Illuminate\Console\Command;

class VerificarPixExpirados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pix:verificar-expirados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica e atualiza status de cobranças PIX expiradas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando cobranças PIX expiradas...');

        try {
            $pixService = new PixService;
            $atualizados = $pixService->verificarExpirados();

            $this->info("✓ {$atualizados} cobrança(s) atualizada(s)");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erro ao verificar expirados: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
