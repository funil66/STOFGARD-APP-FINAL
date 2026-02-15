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
            'tipo' => fake()->randomElement(['entrada', 'saida']),
            'descricao' => fake()->sentence(),
            'valor' => fake()->randomFloat(2, 10, 1000),
            'data' => fake()->date(),
            'data_vencimento' => fake()->date(),
            'status' => 'pendente',
            'forma_pagamento' => fake()->randomElement(['pix', 'boleto', 'cartao_credito']),
        ];
    }
}
