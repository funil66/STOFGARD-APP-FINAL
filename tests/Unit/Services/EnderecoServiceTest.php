<?php

namespace Tests\Unit\Services;

use App\Services\EnderecoService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnderecoServiceTest extends TestCase
{
    public function test_buscar_cep_com_sucesso()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'complemento' => 'lado ímpar',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ], 200),
        ]);

        $endereco = EnderecoService::buscarCep('01001000');

        $this->assertNotNull($endereco);
        $this->assertEquals('Praça da Sé', $endereco['logradouro']);
        $this->assertEquals('São Paulo', $endereco['cidade']);
        $this->assertEquals('SP', $endereco['estado']);
    }

    public function test_buscar_cep_invalido_retorna_null()
    {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
        ]);

        $endereco = EnderecoService::buscarCep('00000000');

        $this->assertNull($endereco);
    }

    public function test_buscar_cep_com_formato_invalido_retorna_null()
    {
        $endereco = EnderecoService::buscarCep('123'); // Menos de 8 dígitos

        $this->assertNull($endereco);
    }
}
