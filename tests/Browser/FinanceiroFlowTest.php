<?php

namespace Tests\Browser;

use App\Models\Financeiro;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinanceiroFlowTest extends DuskTestCase
{
    public function test_baixar_pagamento_via_modal()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $financeiro = Financeiro::factory()->create([
                'valor' => 500,
                'status' => 'pendente',
                'descricao' => 'Teste Dusk Baixar',
            ]);

            $browser->loginAs($user)
                ->visit('/admin/financeiros')
                ->waitForText('Teste Dusk Baixar')
                ->click('.filament-tables-table-actions-column button[title="Registrar Pagamento"]') // Ajuste o seletor conforme necessário
                ->waitForDialog() // Ou modal
                ->within('.filament-modal', function ($modal) {
                    $modal->type('valor_pago', '500')
                        ->select('forma_pagamento', 'pix')
                        ->clickButton('Salvar'); // Ou Confirmar
                })
                ->waitForText('Pagamento confirmado!')
                ->assertSee('Pago');
        });
    }

    public function test_pagamento_parcial_via_interface()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $financeiro = Financeiro::factory()->create([
                'valor' => 1000,
                'status' => 'pendente',
                'descricao' => 'Dusk Parcial',
            ]);

            $browser->loginAs($user)
                ->visit('/admin/financeiros')
                ->waitForText('Dusk Parcial')
                ->click('.filament-tables-table-actions-column button[title="Registrar Pagamento"]')
                ->within('.filament-modal', function ($modal) {
                    $modal->type('valor_pago', '400')
                        ->select('forma_pagamento', 'dinheiro')
                        ->clickButton('Salvar');
                })
                ->waitForText('Pagamento parcial registrado!');

            // Verifica se o original está pago (parcial)
            $browser->visit('/admin/financeiros')
                ->assertSee('Dusk Parcial')
                ->assertSee('Pago');

            // Verifica se o novo registro (saldo) foi criado
            $browser->assertSee('Dusk Parcial (Saldo restante)')
                ->assertSee('R$ 600,00')
                ->assertSee('Pendente');
        });
    }
}
