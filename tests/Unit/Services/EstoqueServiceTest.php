<?php

namespace Tests\Unit\Services;

use App\Models\Estoque;
use App\Services\EstoqueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Filament\Notifications\Notification;

class EstoqueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adicionar_estoque_incrementa_quantidade()
    {
        // Mock Notification para evitar erro de sessão/UI em teste unitário
        // Notification::fake(); removed as it does not exist

        $item = Estoque::create([
            'item' => 'Item Teste',
            'quantidade' => 10,
            'unidade' => 'un',
            'preco_interno' => 10.00,
            'preco_venda' => 20.00,
            'minimo_alerta' => 5,
            'tipo' => 'produto',
        ]);

        EstoqueService::adicionarEstoque($item, 5);

        $this->assertEquals(15, $item->fresh()->quantidade);
    }

    public function test_consumir_estoque_decrementa_quantidade()
    {
        // Notification::fake();

        $item = Estoque::create([
            'item' => 'Item Teste',
            'quantidade' => 10,
            'unidade' => 'un',
            'preco_interno' => 10.00,
            'preco_venda' => 20.00,
            'minimo_alerta' => 5,
            'tipo' => 'produto',
        ]);

        EstoqueService::consumirEstoque($item, 3);

        $this->assertEquals(7, $item->fresh()->quantidade);
    }

    public function test_nao_consumir_estoque_se_insuficiente()
    {
        // Notification::fake();

        $item = Estoque::create([
            'item' => 'Item Teste',
            'quantidade' => 5,
            'unidade' => 'un',
            'preco_interno' => 10.00,
            'preco_venda' => 20.00,
            'minimo_alerta' => 5,
            'tipo' => 'produto',
        ]);

        EstoqueService::consumirEstoque($item, 10);

        // Quantidade deve permanecer inalterada
        $this->assertEquals(5, $item->fresh()->quantidade);
    }
}
