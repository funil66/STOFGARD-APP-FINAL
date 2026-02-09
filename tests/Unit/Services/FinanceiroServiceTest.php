<?php

namespace Tests\Unit\Services;

use App\Models\Financeiro;
use App\Services\FinanceiroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Filament\Notifications\Notification;

class FinanceiroServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_baixar_pagamento_atualiza_status()
    {
        $financeiro = Financeiro::factory()->create([
            'status' => 'pendente',
            'valor' => 100.00,
            'tipo' => 'entrada',
        ]);

        FinanceiroService::baixarPagamento($financeiro);

        $this->assertEquals('pago', $financeiro->fresh()->status);
        $this->assertNotNull($financeiro->fresh()->data_pagamento);
    }

    public function test_estornar_pagamento_reverte_status()
    {
        $financeiro = Financeiro::factory()->create([
            'status' => 'pago',
            'valor' => 100.00,
            'tipo' => 'entrada',
            'data_pagamento' => now(),
        ]);

        FinanceiroService::estornarPagamento($financeiro);

        $this->assertEquals('pendente', $financeiro->fresh()->status);
        $this->assertNull($financeiro->fresh()->data_pagamento);
    }
}
