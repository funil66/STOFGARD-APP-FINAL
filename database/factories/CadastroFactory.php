<?php

namespace Database\Factories;

use App\Models\Cadastro;
use Illuminate\Database\Eloquent\Factories\Factory;

class CadastroFactory extends Factory
{
    protected $model = Cadastro::class;

    public function definition()
    {
        return [
            'nome' => fake()->name(),
            'tipo' => 'cliente',
            'documento' => fake()->numerify('###########'),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->phoneNumber(),
            'cep' => fake()->postcode(),
            'logradouro' => fake()->streetName(),
            'numero' => fake()->buildingNumber(),
            'bairro' => fake()->word(),
            'cidade' => fake()->city(),
            'estado' => fake()->stateAbbr(),
        ];
    }

    // Estados específicos por tipo
    public function loja()
    {
        return $this->state(fn(array $attributes) => [
            'tipo' => 'loja',
            'comissao_percentual' => 10.00,
        ]);
    }

    public function vendedor()
    {
        return $this->state(fn(array $attributes) => [
            'tipo' => 'vendedor',
            'comissao_percentual' => 5.00,
        ]);
    }

    public function arquiteto()
    {
        return $this->state(fn(array $attributes) => [
            'tipo' => 'arquiteto',
            'comissao_percentual' => 7.00,
        ]);
    }
}
