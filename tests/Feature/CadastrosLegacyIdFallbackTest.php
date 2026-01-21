<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;

class CadastrosLegacyIdFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_id_redirects_to_uuid()
    {
        $c = Cliente::factory()->create(['nome' => 'Legacy Cliente']);

        $resp = $this->get('/cadastros/' . $c->id);

        $resp->assertStatus(301);
        $resp->assertRedirect(route('cadastros.show', ['uuid' => $c->uuid]));
    }
}
