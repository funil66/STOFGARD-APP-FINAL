<?php

namespace Tests\Feature;

use App\Models\Financeiro;
use App\Models\OrdemServico;
use App\Services\FinanceiroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceiroLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_baixar_pagamento_total()
    {
        $financeiro = Financeiro::factory()->create([
            'valor' => 1000,
            'status' => 'pendente',
            'valor_pago' => 0,
        ]);

        FinanceiroService::baixarPagamento($financeiro, [
            'valor_pago' => 1000,
            'forma_pagamento' => 'pix',
            'data_pagamento' => now(),
        ]);

        $this->assertEquals('pago', $financeiro->fresh()->status);
        $this->assertEquals(1000, $financeiro->fresh()->valor_pago);
        $this->assertEquals('pix', $financeiro->fresh()->forma_pagamento);
    }

    public function test_baixar_pagamento_parcial()
    {
        $financeiro = Financeiro::factory()->create([
            'valor' => 1000,
            'status' => 'pendente',
            'descricao' => 'Teste Parcial',
            'valor_pago' => 0,
        ]);

        FinanceiroService::baixarPagamento($financeiro, [
            'valor_pago' => 400,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => now(),
        ]);

        // Verifica registro original (pago parcial)
        $this->assertEquals('pago', $financeiro->fresh()->status);
        $this->assertEquals(400, $financeiro->fresh()->valor_pago);
        $this->assertStringContainsString('Pagamento parcial', $financeiro->fresh()->observacoes);

        // Verifica novo registro (saldo restante)
        $novoRegistro = Financeiro::where('descricao', 'like', 'Teste Parcial (Saldo restante)')->first();
        $this->assertNotNull($novoRegistro);
        $this->assertEquals(600, $novoRegistro->valor);
        $this->assertEquals('pendente', $novoRegistro->status);
        $this->assertEquals(0, $novoRegistro->valor_pago);
    }

    public function test_estornar_pagamento()
    {
        $financeiro = Financeiro::factory()->create([
            'valor' => 500,
            'status' => 'pago',
            'valor_pago' => 500,
        ]);

        FinanceiroService::estornarPagamento($financeiro);

        $this->assertEquals('pendente', $financeiro->fresh()->status);
        $this->assertEquals(0, $financeiro->fresh()->valor_pago);
        $this->assertNull($financeiro->fresh()->data_pagamento);
    }

    public function test_baixar_em_lote()
    {
        $f1 = Financeiro::factory()->create(['valor' => 100, 'status' => 'pendente']);
        $f2 = Financeiro::factory()->create(['valor' => 200, 'status' => 'pendente']);

        FinanceiroService::baixarEmLote(collect([$f1, $f2]));

        $this->assertEquals('pago', $f1->fresh()->status);
        $this->assertEquals(100, $f1->fresh()->valor_pago);

        $this->assertEquals('pago', $f2->fresh()->status);
        $this->assertEquals(200, $f2->fresh()->valor_pago);
    }
}
