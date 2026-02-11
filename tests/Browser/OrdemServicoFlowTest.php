<?php

namespace Tests\Browser;

use App\Models\OrdemServico;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrdemServicoFlowTest extends DuskTestCase
{
    public function test_receber_os_pagamento_parcial()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $os = OrdemServico::factory()->create([
                'status' => 'aberta',
                'valor_total' => 2000,
                'data_conclusao' => now(),
            ]);

            // Garante que não tem financeiro ainda
            $this->assertNull($os->financeiro);

            $browser->loginAs($user)
                ->visit('/admin/ordem-servicos')
                ->waitForText($os->numero_os)
                // Clica em Receber (Ação de tabela)
                ->click(".filament-tables-table-actions-column button[title='Receber Pagamento']")
                ->waitForDialog()
                ->within('.filament-modal', function ($modal) {
                    $modal->type('valor_pago', '1000') // Paga metade
                        ->select('forma_pagamento', 'pix')
                        ->clickButton('Salvar');
                })
                ->waitForText('Pagamento Registrado!');

            // Verifica Financeiro criado e status parcial
            $financeiro = \App\Models\Financeiro::where('ordem_servico_id', $os->id)->where('valor_pago', 1000)->first();
            $this->assertNotNull($financeiro);
            $this->assertEquals('pago', $financeiro->status);

            // Verifica se gerou saldo restante
            $saldo = \App\Models\Financeiro::where('ordem_servico_id', $os->id)->where('status', 'pendente')->first();
            $this->assertNotNull($saldo);
            $this->assertEquals(1000, $saldo->valor);
        });
    }
}
