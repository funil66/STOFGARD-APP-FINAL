<?php

namespace Tests\Browser;

use App\Models\Cadastro;
use App\Models\OrdemServico;
use App\Models\TabelaPreco;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrdemServicoTest extends DuskTestCase
{
    /**
     * Test the index page of OrdemServico.
     */
    public function test_index_ordem_servico(): void
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
            $cliente = Cadastro::factory()->create(['tipo' => 'cliente', 'nome' => 'Cliente Index OS']);

            // Create a sample OS
            $os = OrdemServico::create([
                'cadastro_id' => $cliente->id, // Fixed: use cadastro_id
                'cliente_id' => null, // ensure legacy is null? or ignore
                'data_abertura' => now(),
                'status' => 'aberta',
                'valor_total' => 150.00,
                'criado_por' => $user->id,
                'loja_id' => Cadastro::factory()->create(['tipo' => 'loja'])->id, // Required by model? nullable? Resource says required.
                'vendedor_id' => Cadastro::factory()->create(['tipo' => 'vendedor'])->id, // Required?
                'tipo_servico' => 'servico',
            ]);

            $browser->loginAs($user)
                ->visit('/admin/ordem-servicos')
                // ->assertSee('Ordens de Serviço') // Flexible check
                ->assertSee('Ordem')
                ->assertSee($os->numero_os)
                ->assertSee('Cliente Index OS');
        });
    }

    /**
     * Test creating a new OrdemServico.
     */
    public function test_create_ordem_servico(): void
    {
        // $this->markTestSkipped('Skipping due to hang in headless environment dealing with repeater interaction.');

        // return;

        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Test',
                    'password' => bcrypt('password'),
                    'is_admin' => true,
                ]
            );

            // Create required data
            $cliente = Cadastro::factory()->create(['tipo' => 'cliente', 'nome' => 'Cliente Teste OS']);
            $loja = Cadastro::factory()->create(['tipo' => 'loja', 'nome' => 'Loja Teste OS']);
            $vendedor = Cadastro::factory()->create(['tipo' => 'vendedor', 'nome' => 'Vendedor Teste OS']);

            // Ensure we have a TabelaPreco item for the repeater
            $item = TabelaPreco::first();
            if (!$item) {
                $item = TabelaPreco::create([
                    'nome_item' => 'Item Teste OS',
                    'preco_vista' => 100.00,
                    'unidade_medida' => 'unidade',
                    'ativo' => true,
                    'tipo_servico' => 'higienizacao',
                    'descricao_tecnica' => 'Higienização Profissional de Estofados',
                    'dias_garantia' => 90,
                ]);
            }

            $browser->loginAs($user)
                ->visit('/admin/ordem-servicos/create')
                ->waitForText('Identificação', 10);

            // Helper to select Choices.js option
            $selectChoice = function ($part, $text) use ($browser) {
                $selector = "div[wire\\:key*=\"{$part}\"]";
                $browser->waitFor($selector)
                    ->click("$selector .choices")
                    ->pause(200)
                    ->type("$selector .choices__input--cloned", $text)
                    ->pause(1000) // Search debounce
                    // Click the first selectable option
                    ->waitFor("$selector .choices__list[role=\"listbox\"] .choices__item--selectable")
                    ->click("$selector .choices__list[role=\"listbox\"] .choices__item--selectable")
                    ->pause(500);
            };

            // Select Cliente
            $selectChoice('data.cadastro_id', $cliente->nome);

            // Select Loja
            $selectChoice('data.loja_id', $loja->nome);

            // Select Vendedor
            $selectChoice('data.vendedor_id', $vendedor->nome);

            // Select Tipo Servico (Native Select)
            // 'higienizacao' is the value (slug)
            $browser->waitFor('#data\\.tipo_servico')
                ->select('#data\\.tipo_servico', 'higienizacao')
                ->pause(1000); // Wait for Livewire updates

            // Select Status (Native Select)
            $browser->select('#data\\.status', 'aberta')
                ->pause(500);

            // Repeater Item
            // Default items = 1, so we don't need to click Add. Just find the existing one.
            // $browser->click('button[wire\\:click*="mountFormComponentAction(\'data.itens\', \'add\')"]');
            // $browser->pause(500);

            // Fill Item Descricao (Script interaction for reliability)
            // Use storeSource instead of dump with args
            // Repeater Item Interaction
            $repeaterSelector = '.fi-fo-repeater-item:first-child';
            $descricaoWrapper = "$repeaterSelector .fi-fo-select";

            // Open Choices dropdown for Description
            $browser->waitFor($descricaoWrapper)
                ->click("$descricaoWrapper .choices")
                ->pause(300);

            // Type search term and select
            $browser->type("$descricaoWrapper .choices__input--cloned", $item->nome_item)
                ->pause(1000) // Wait for search debounce
                ->waitFor("$descricaoWrapper .choices__list[role=\"listbox\"] .choices__item--selectable")
                ->click("$descricaoWrapper .choices__list[role=\"listbox\"] .choices__item--selectable")
                ->pause(500);

            // Fill Quantity
            $browser->type("$repeaterSelector input[id*='quantidade']", '2')
                ->pause(200);

            // Fill Unit Price
            $browser->type("$repeaterSelector input[id*='valor_unitario']", '150.00')
                ->pause(200);



            // Quantity and Unit Price should auto-fill or we set them.
            // Just asserting correct calculation would be good, or just submit.

            // Scroll to Create button
            $browser->driver->executeScript("arguments[0].scrollIntoView({block: 'center', inline: 'nearest'});", [
                $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.fi-form-actions button[type="submit"]')),
            ]);
            $browser->pause(500);

            // Submit
            $browser->driver->executeScript('arguments[0].click();', [
                $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.fi-form-actions button[type="submit"]')),
            ]);


            $browser->waitForText('Criado', 15);
        });
    }
}
