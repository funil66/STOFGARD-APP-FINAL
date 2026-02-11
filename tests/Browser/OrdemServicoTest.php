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
     * Test the full lifecycle of an OS: Create -> Add Items -> Conclude -> Verify.
     */
    public function test_os_lifecycle_flow(): void
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

            // Setup Data
            $cliente = Cadastro::factory()->create(['tipo' => 'cliente', 'nome' => 'Cliente Lifecycle OS']);
            $loja = Cadastro::factory()->create(['tipo' => 'loja']);
            $vendedor = Cadastro::factory()->create(['tipo' => 'vendedor']);

            $servico = TabelaPreco::firstOrCreate(
                ['nome_item' => 'Servico Teste Lifecycle'],
                ['preco_vista' => 150.00, 'unidade_medida' => 'un', 'tipo_servico' => 'servico']
            );

            $browser->loginAs($user)
                ->visit('/admin/ordem-servicos/create')
                ->waitForText('Identificação', 10)

                // Selects (using click/type for Choices.js or native)
                // Assuming standard filament choices for relations
                ->click('div[wire\\:key*="data.cadastro_id"] .choices')
                ->type('div[wire\\:key*="data.cadastro_id"] .choices__input--cloned', 'Cliente Lifecycle OS')
                ->pause(1000)
                ->click('div[wire\\:key*="data.cadastro_id"] .choices__list[role="listbox"] .choices__item--selectable')

                ->select('data.loja_id', $loja->id) // If native select, or adapt if choices
                ->select('data.vendedor_id', $vendedor->id)

                ->type('data.descricao_servico', 'Descrição Completa do Serviço')

                // Add Item
                ->click('button[wire\\:click*="mountFormComponentAction(\'data.itens\', \'add\')"]')
                ->pause(500)
                // Select Item
                ->click('.fi-fo-repeater-item:first-child .choices')
                ->type('.fi-fo-repeater-item:first-child .choices__input--cloned', 'Servico Teste Lifecycle')
                ->pause(1000)
                ->click('.fi-fo-repeater-item:first-child .choices__list[role="listbox"] .choices__item--selectable')

                ->press('Criar')
                ->waitForText('Criado', 10);

            // Verify in List
            $browser->visit('/admin/ordem-servicos')
                ->waitForText('Cliente Lifecycle OS')
                ->assertSee('Aberta');

            // Test "Concluir" Action (Lifecycle)
            // Need to find the specific row. Assuming it's the top one or filter.
            $browser->click('button[title="Concluir OS"]') // From standardizing step
                ->waitForText('Tem certeza', 5)
                ->press('Confirmar')
                ->waitForText('Concluída', 10);

            // Verify Status Update
            $browser->assertSee('Concluída');
        });
    }
}
