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
            'nome' => $this->faker->company,
            'razao_social' => $this->faker->company,
            'cnpj_cpf' => $this->faker->numerify('###########'),
            'email' => $this->faker->companyEmail,
            'telefone' => $this->faker->phoneNumber,
            'celular' => $this->faker->phoneNumber,
            'cep' => '14000-000',
            'logradouro' => $this->faker->streetName,
            'numero' => $this->faker->buildingNumber,
            'bairro' => $this->faker->citySuffix,
            'cidade' => $this->faker->city,
            'estado' => 'SP',
            'percentual_comissao' => 10,
            'ativo' => true,
            'registrado_por' => 'UT',
        ];
    }
}
