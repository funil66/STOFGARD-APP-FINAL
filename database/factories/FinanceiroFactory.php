<?php

namespace Database\Factories;

use App\Models\Cadastro;
use App\Models\Categoria;
use App\Models\Financeiro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Financeiro>
 */
class FinanceiroFactory extends Factory
{
    protected $model = Financeiro::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cadastro_id' => Cadastro::factory(),
            'categoria_id' => Categoria::factory(), // Assuming CategoriaFactory exists, otherwise might need adjustment
            'tipo' => $this->faker->randomElement(['entrada', 'saida']),
            'descricao' => $this->faker->sentence(),
            'valor' => $this->faker->randomFloat(2, 10, 1000),
            'data' => $this->faker->date(),
            'data_vencimento' => $this->faker->date(),
            'status' => 'pendente',
            'forma_pagamento' => $this->faker->randomElement(['pix', 'boleto', 'cartao_credito']),
        ];
    }
}
