<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrcamentoFactory extends Factory
{
    protected $model = Orcamento::class;

    public function definition()
    {
        return [
            'cliente_id' => Cliente::factory(),
            'criado_por' => User::factory(),
            'numero_orcamento' => Orcamento::gerarNumeroOrcamento(),
            'descricao_servico' => $this->faker->sentence(),
            'data_orcamento' => now(),
            'data_validade' => now()->addDays(7),
            'tipo_servico' => $this->faker->word,
            'valor_total' => $this->faker->randomFloat(2, 100, 1000),
            'status' => 'em_elaboracao',
            'data_servico_agendada' => now()->addDays(10),
        ];
    }
}
