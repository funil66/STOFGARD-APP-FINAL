<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cadastro;

class CadastroEnderecoTest extends TestCase
{
    public function test_format_endereco_uses_endereco_completo()
    {
        $cadastro = new Cadastro();
        $cadastro->endereco_completo = 'Rua das Flores, 123 - Centro';

        $this->assertEquals('Rua das Flores, 123 - Centro', $cadastro->formatEnderecoCompleto());
    }

    public function test_format_endereco_composes_from_fields()
    {
        $cadastro = new Cadastro([
            'logradouro' => 'Avenida Brasil',
            'numero' => '200',
            'complemento' => 'Apto 12',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '01000-000',
            'endereco_completo' => null,
        ]);

        $expected = 'Avenida Brasil, nº 200, Apto 12, Bela Vista, São Paulo, SP, CEP: 01000-000';
        $this->assertEquals($expected, $cadastro->formatEnderecoCompleto());
    }
}
