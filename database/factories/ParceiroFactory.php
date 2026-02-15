<?php

namespace Database\Factories;

use App\Models\Parceiro;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParceiroFactory extends Factory
{
    protected $model = Parceiro::class;

    public function definition()
    {
        return [
            'tipo' => 'loja',
            'nome' => fake()->company,
            'razao_social' => fake()->company,
            'cnpj_cpf' => fake()->numerify('###########'),
            'email' => fake()->companyEmail,
            'telefone' => fake()->phoneNumber,
            'celular' => fake()->phoneNumber,
            'cep' => '14000-000',
            'logradouro' => fake()->streetName,
            'numero' => fake()->buildingNumber,
            'bairro' => fake()->citySuffix,
            'cidade' => fake()->city,
            'estado' => 'SP',
            'percentual_comissao' => 10,
            'ativo' => true,
            'registrado_por' => 'UT',
        ];
    }
}
