<?php

namespace Tests\Feature\Observers;

use App\Models\Estoque;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstoqueObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_estoque_created_updates_produto_quantity()
    {
        $produto = Produto::create([
            'nome' => 'Peça de Teste',
            'estoque_atual' => 10,
        ]);

        $estoque = Estoque::create([
            'item' => 'Peça de Teste',
            'quantidade' => 15,
            'valor' => 100,
        ]);

        $produto->refresh();
        $this->assertEquals(15, $produto->estoque_atual);
    }
}
