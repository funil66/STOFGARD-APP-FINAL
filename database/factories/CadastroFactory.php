<?php

namespace Database\Factories;

use App\Models\Cadastro;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CadastroFactory extends Factory
{
    protected $model = Cadastro::class;

    public function definition()
    {
        $suffix = Str::lower(Str::random(8));
        $numero = (string) random_int(1, 9999);

        return [
            'nome' => 'Cliente '.$suffix,
            'tipo' => 'cliente',
            'documento' => (string) random_int(10000000000, 99999999999),
            'email' => 'cliente_'.$suffix.'@example.com',
            'telefone' => '(16) 9'.random_int(1000, 9999).'-'.random_int(1000, 9999),
            'cep' => (string) random_int(10000000, 99999999),
            'logradouro' => 'Rua '.$suffix,
            'numero' => $numero,
            'bairro' => 'Centro',
            'cidade' => 'Ribeirão Preto',
            'estado' => 'SP',
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
