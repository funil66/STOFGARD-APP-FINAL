<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition()
    {
        return [
            'nome' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'telefone' => $this->faker->phoneNumber,
            'celular' => $this->faker->phoneNumber,
            'cpf_cnpj' => $this->faker->numerify('###########'),
            'cep' => $this->faker->postcode,
            'logradouro' => $this->faker->streetName,
            'numero' => $this->faker->buildingNumber,
            'bairro' => $this->faker->word,
            'cidade' => $this->faker->city,
            'estado' => $this->faker->stateAbbr,
        ];
    }
}
