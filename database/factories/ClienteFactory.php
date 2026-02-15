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
            'nome' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'telefone' => fake()->phoneNumber,
            'celular' => fake()->phoneNumber,
            'cpf_cnpj' => fake()->numerify('###########'),
            'cep' => fake()->postcode,
            'logradouro' => fake()->streetName,
            'numero' => fake()->buildingNumber,
            'bairro' => fake()->word,
            'cidade' => fake()->city,
            'estado' => fake()->stateAbbr,
        ];
    }
}
