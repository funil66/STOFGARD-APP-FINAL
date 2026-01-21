<?php

namespace Tests\Feature;

use App\Models\Parceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastroTypeRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lojas_route_shows_lojas()
    {
        $loja = Parceiro::factory()->create(['tipo' => 'loja', 'nome' => 'Loja X']);
        $vendedor = Parceiro::factory()->create(['tipo' => 'vendedor', 'nome' => 'Vendedor Y']);

        $resp = $this->get(route('cadastros.lojas'));

        $resp->assertStatus(200);
        $resp->assertSeeText('Loja X');
        $resp->assertDontSeeText('Vendedor Y');
    }

    public function test_vendedores_route_shows_vendedores()
    {
        $vendedor = Parceiro::factory()->create(['tipo' => 'vendedor', 'nome' => 'Vendedor Y']);
        $loja = Parceiro::factory()->create(['tipo' => 'loja', 'nome' => 'Loja X']);

        $resp = $this->get(route('cadastros.vendedores'));

        $resp->assertStatus(200);
        $resp->assertSeeText('Vendedor Y');
        $resp->assertDontSeeText('Loja X');
    }
}
