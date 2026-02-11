<?php

namespace Tests\Unit;

use App\Models\Cadastro;
use App\Models\Estoque;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstoqueDeductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_is_deducted_when_os_is_concluded(): void
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $this->actingAs($user);

        $cliente = Cadastro::factory()->create(['tipo' => 'cliente']);
        $loja = Cadastro::factory()->create(['tipo' => 'loja']);
        $vendedor = Cadastro::factory()->create(['tipo' => 'vendedor']);

        // Create Stock Item
        $estoque = Estoque::create([
            'item' => 'Produto Teste',
            'quantidade' => 10.00,
            'unidade' => 'un',
            'valor_venda' => 50.00,
            'valor_custo' => 30.00,
        ]);

        // 2. Create OS
        $os = OrdemServico::create([
            'cadastro_id' => $cliente->id,
            'loja_id' => $loja->id,
            'vendedor_id' => $vendedor->id,
            'status' => 'aberta',
            'valor_total' => 100.00,
            'tipo_servico' => 'servico',
            'numero_os' => 'OS-STOCK-TEST',
            'criado_por' => $user->id,
        ]);

        //Attach Product to OS
        $os->produtosUtilizados()->attach($estoque->id, [
            'quantidade_utilizada' => 3.00,
            'unidade' => 'un',
        ]);

        // 3. Conclude OS
        $os->update(['status' => 'concluida']);

        // 4. Verify Stock
        $estoque->refresh();
        $this->assertEquals(7.00, $estoque->quantidade);
    }
}
