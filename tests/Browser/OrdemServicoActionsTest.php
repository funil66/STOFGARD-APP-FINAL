<?php

namespace Tests\Browser;

use App\Models\Cadastro;
use App\Models\OrdemServico;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrdemServicoActionsTest extends DuskTestCase
{
    public function test_os_actions_visibility_and_execution(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Test',
                    'password' => bcrypt('password'),
                    'is_admin' => true,
                ]
            );

            // Create dependencies
            $cliente = Cadastro::factory()->create(['tipo' => 'cliente', 'nome' => 'Cliente Actions OS']);
            $loja = Cadastro::factory()->create(['tipo' => 'loja']);
            $vendedor = Cadastro::factory()->create(['tipo' => 'vendedor']);

            // Create OS
            $os = OrdemServico::create([
                'cadastro_id' => $cliente->id,
                'loja_id' => $loja->id,
                'vendedor_id' => $vendedor->id,
                'data_abertura' => now(),
                'status' => 'aberta',
                'valor_total' => 200.00,
                'tipo_servico' => 'servico',
                'numero_os' => 'OS-TEST-ACTIONS', // Mocked or auto-generated
                'criado_por' => $user->id,
            ]);

            $browser->loginAs($user)
                ->visit('/admin/ordem-servicos')
                ->waitForText('OS-TEST-ACTIONS', 10);

            // 1. Check Buttons Presence using Tooltips or Icons
            // Ficha
            $browser->assertPresent('button[title="Imprimir Ficha"]') // Tooltip 'Imprimir Ficha'
                // Baixar
                ->assertPresent('button[title="Baixar / Receber"]')
                // Concluir
                ->assertPresent('button[title="Concluir OS"]')
                // Excluir
                ->assertPresent('button[title="Excluir"]');

            // 2. Test Baixar
            $browser->click('button[title="Baixar / Receber"]')
                ->waitForText('Data do Pagamento', 5) // Modal opened
                ->type('valor_pago', '200.00')
                ->select('forma_pagamento', 'pix')
                // Submit modal. The button usually says "Baixar" or "Submit".
                // Filament actions usually have a specific confirm button. 
                // We can look for the button in the modal footer.
                ->press('Confirmar') // Or whatever the submit button label is. Default is often 'Confirm' or 'Submit' or the Action label 'Baixar'.
                ->waitForText('Pagamento Registrado!', 10);

            // Verify Financeiro created (database check or via UI if possible)
            // We can check database in the test itself.
            $this->assertDatabaseHas('financeiros', [
                'ordem_servico_id' => $os->id,
                'status' => 'pago',
                'valor_pago' => 200.00,
            ]);

            // 3. Test Concluir
            $browser->click('button[title="Concluir OS"]')
                ->waitForText('Tem certeza que deseja marcar esta OS como concluída?', 5)
                ->press('Confirmar')
                ->waitForText('Concluída', 5); // Status badge update?

            $os->refresh();
            $this->assertEquals('concluida', $os->status);
        });
    }
}
