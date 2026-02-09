<?php

namespace Database\Factories;

use App\Models\Cadastro;
use App\Models\Orcamento;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrcamentoFactory extends Factory
{
    protected $model = Orcamento::class;

    public function definition()
    {
        return [
            'cadastro_id' => Cadastro::factory(),
            'numero' => Orcamento::gerarNumeroOrcamento(),
            'data_orcamento' => now(),
            'data_validade' => now()->addDays(7),
            'valor_total' => $this->faker->randomFloat(2, 100, 1000),
            'status' => 'em_elaboracao',
        ];
    }
}
