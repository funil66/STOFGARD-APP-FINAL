<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orcamento;

class RecalculateOrcamentos extends Command
{
    protected $signature = 'stofgard:recalc-orcamentos';
    protected $description = 'Recalcula e persiste os totais de todos os orçamentos (valor_subtotal, valor_desconto, valor_total)';

    public function handle(): int
    {
        $this->info('Recalculando orçamentos...');

        $count = 0;
        Orcamento::withTrashed()->chunk(100, function ($orcamentos) use (&$count) {
            foreach ($orcamentos as $orcamento) {
                $orcamento->calcularTotal();
                $orcamento->saveQuietly();
                $count++;
            }
        });

        $this->info("Finalizado. Processados: {$count} orçamentos.");

        return 0;
    }
}
