<?php

namespace Tests\Feature\Observers;

use App\Models\Produto;
use App\Models\Estoque;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_produto_creation_creates_estoque_item()
    {
        $produto = Produto::create([
            'nome' => 'Cabo XYZ',
            'estoque_atual' => 50,
            'preco_custo' => 10.0,
            'preco_venda' => 20.0,
        ]);
        
        $this->assertDatabaseHas('estoques', [
            'item' => 'Cabo XYZ',
            'quantidade' => 50,
            'preco_interno' => 10.0,
            'preco_venda' => 20.0,
        ]);
    }
}
