<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Cadastro;
use App\Models\TabelaPreco;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrcamentoTest extends DuskTestCase
{
    public function test_create_orcamento_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
            );

            $cliente = Cadastro::factory()->create(['tipo' => 'cliente', 'nome' => 'Cliente Orcamento Dusk']);

            // Ensure Item exists
            $item = TabelaPreco::firstOrCreate(
                ['nome_item' => 'Item Orcamento Dusk'],
                ['preco_vista' => 200.00, 'unidade_medida' => 'un', 'tipo_servico' => 'servico']
            );

            $browser->loginAs($user)
                ->visit('/admin/orcamentos/create')
                ->waitForText('Dados do Orçamento', 10)

                // Select Cliente (Choices.js)
                ->click('div[wire\\:key*="data.cadastro_id"] .choices')
                ->type('div[wire\\:key*="data.cadastro_id"] .choices__input--cloned', 'Cliente Orcamento Dusk')
                ->pause(1000)
                ->click('div[wire\\:key*="data.cadastro_id"] .choices__list[role="listbox"] .choices__item--selectable')

                // Add Item (Repeater) - Assuming 1 item default or explicitly adding
                // If default 0, click add. Usually schemas start with 0 or 1. Let's assume 0 for repeater?
                // Checking Resource: ->defaultItems(0) not specified usually means 0 unless set.
                // Resource Check: 'itens' repeater.
                ->click('button[wire\\:click*="mountFormComponentAction(\'data.itens\', \'add\')"]')
                ->pause(500)

                // Select Item in Repeater
                ->click('.fi-fo-repeater-item:first-child .choices')
                ->type('.fi-fo-repeater-item:first-child .choices__input--cloned', 'Item Orcamento Dusk')
                ->pause(1000)
                ->click('.fi-fo-repeater-item:first-child .choices__list[role="listbox"] .choices__item--selectable')

                // Quantity and Price should autofill, verify total?
                ->pause(500)
                ->assertValue('.fi-fo-repeater-item:first-child input[id*="valor_unitario"]', '200.00')

                ->press('Criar')
                ->waitForText('Criado', 10);

            // Verify in Index
            $browser->visit('/admin/orcamentos')
                ->waitForText('Cliente Orcamento Dusk');
        });
    }

    public function test_orcamento_actions(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'admin@test.com')->first();
            // Create an Orcamento via Factory
            $orcamento = \App\Models\Orcamento::factory()->create([
                'status' => 'aprovado', // Already approved
                'cadastro_id' => Cadastro::factory()->create(['nome' => 'Cliente Aprovado'])->id,
            ]);

            $browser->loginAs($user)
                ->visit('/admin/orcamentos')
                ->waitForText('Cliente Aprovado')
                // Test PDF Action
                ->click('a[href*="/pdf"]') // Assuming Action is link or button. Standard Filament action uses button or a tag. 
                // Note: PDF usually opens new tab. Dusk can switch tabs using driver->getWindowHandles().
                ->pause(1000);

            $window = collect($browser->driver->getWindowHandles())->last();
            $browser->driver->switchTo()->window($window);
            $browser->assertUrlIs(route('orcamento.pdf', $orcamento));
        });
    }
}
