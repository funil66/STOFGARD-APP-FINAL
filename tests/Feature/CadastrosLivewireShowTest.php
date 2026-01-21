<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Parceiro;

class CadastrosLivewireShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_renders_cliente_fields()
    {
        $c = Cliente::factory()->create(['nome' => 'Cliente Livewire Test', 'email' => 'live@example.com']);

        $resp = $this->get(route('cadastros.show', ['uuid' => $c->uuid]));

        $resp->assertStatus(200);
        $resp->assertSee('Cliente Livewire Test');
        $resp->assertSee('live@example.com');
    }

    public function test_show_renders_parceiro_fields()
    {
        $p = Parceiro::factory()->create(['nome' => 'Loja Test', 'tipo' => 'loja', 'razao_social' => 'Razao Ltda']);

        $resp = $this->get(route('cadastros.show', ['uuid' => $p->uuid]));

        $resp->assertStatus(200);
        $resp->assertSee('Loja Test');
        $resp->assertSee('Razao Ltda');
    }
}
