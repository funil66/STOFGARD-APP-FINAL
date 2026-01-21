<?php

namespace Tests\Feature;

use App\Models\Parceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParceiroLojaVendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_belongs_to_loja_and_loja_has_vendedores()
    {
        $loja = Parceiro::factory()->create([
            'tipo' => 'loja',
            'nome' => 'Loja Teste',
        ]);

        $vendedor = Parceiro::factory()->create([
            'tipo' => 'vendedor',
            'nome' => 'Vendedor Teste',
            'loja_id' => $loja->id,
        ]);

        $this->assertNotNull($vendedor->loja);
        $this->assertEquals($loja->id, $vendedor->loja->id);

        $this->assertTrue($loja->vendedores()->count() >= 1);
        $this->assertTrue($loja->vendedores->contains($vendedor));
    }
}
