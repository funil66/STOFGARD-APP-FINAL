<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Orcamento;
use Illuminate\Database\QueryException;

class OrcamentoSampleSeeder extends Seeder
{
    public function run()
    {
        // Cria alguns orçamentos de exemplo para facilitar testes locais
        for ($i = 0; $i < 3; $i++) {
            $attempts = 0;

            while (true) {
                try {
                    Orcamento::factory()->create();
                    break;
                } catch (QueryException $e) {
                    // Em caso de conflito de unique (numero_orcamento), tenta novamente
                    $attempts++;
                    if ($attempts > 5) {
                        throw $e;
                    }
                    usleep(100000); // aguarda 100ms e tenta de novo
                }
            }
        }
    }
}
