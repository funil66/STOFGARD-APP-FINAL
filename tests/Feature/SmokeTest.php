<?php

namespace Tests\Feature;

use App\Models\Cadastro;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_is_up()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_can_create_cadastro_via_factory()
    {
        $cadastro = Cadastro::factory()->create();
        $this->assertDatabaseHas('cadastros', ['id' => $cadastro->id]);
    }

    public function test_can_create_cadastro_loja_via_factory()
    {
        $loja = Cadastro::factory()->loja()->create();
        $this->assertEquals('loja', $loja->tipo);
        $this->assertDatabaseHas('cadastros', ['id' => $loja->id, 'tipo' => 'loja']);
    }

    public function test_can_create_orcamento_linked_to_cadastro()
    {
        $cadastro = Cadastro::factory()->create();
        $orcamento = Orcamento::factory()->create(['cadastro_id' => $cadastro->id]);

        $this->assertNotNull($orcamento->id);
        $this->assertEquals($cadastro->id, $orcamento->cadastro_id);
    }
}
