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
            'nome' => $this->faker->name,
            'tipo' => 'cliente',
            'documento' => $this->faker->numerify('###########'),
            'email' => $this->faker->unique()->safeEmail,
            'telefone' => $this->faker->phoneNumber,
            'cep' => $this->faker->postcode,
            'logradouro' => $this->faker->streetName,
            'numero' => $this->faker->buildingNumber,
            'bairro' => $this->faker->word,
            'cidade' => $this->faker->city,
            'estado' => $this->faker->stateAbbr,
        ];
    }

    // Estados específicos por tipo
    public function loja()
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'loja',
            'comissao_percentual' => 10.00,
        ]);
    }

    public function vendedor()
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'vendedor',
            'comissao_percentual' => 5.00,
        ]);
    }

    public function arquiteto()
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'arquiteto',
            'comissao_percentual' => 7.00,
        ]);
    }
}
