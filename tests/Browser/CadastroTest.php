<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CadastroTest extends DuskTestCase
{
    public function test_create_cliente(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
            );

            $browser->loginAs($user)
                ->visit('/admin/cadastros')
                ->waitForText('Cadastros')
                ->click('a[href*="/create"]') // Standard Filament Create Button
                ->waitForText('Novo Cadastro')
                ->type('nome', 'Cliente Dusk Teste')
                ->select('tipo', 'cliente')
                ->type('email', 'cliente_dusk@teste.com')
                ->type('telefone', '11999998888')
                ->type('documento', '12345678900') // CPF
                ->press('Criar')
                ->waitForText('Criado', 10)
                ->assertSee('Cliente Dusk Teste');
        });
    }

    public function test_edit_cliente(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'admin@test.com')->first();

            // Ensure client exists (created in previous test or factory)
            // Ideally use factory for isolation, but for flow test we can reuse or create new
            $cliente = \App\Models\Cadastro::firstOrCreate(
                ['email' => 'cliente_edit_dusk@teste.com'],
                ['nome' => 'Cliente Edit Dusk', 'tipo' => 'cliente', 'documento' => '11122233344']
            );

            $browser->loginAs($user)
                ->visit("/admin/cadastros/{$cliente->id}/edit")
                ->waitForText('Editar Cadastro')
                ->type('nome', 'Cliente Editado Dusk')
                ->press('Salvar')
                ->waitForText('Salvo', 10)
                ->assertSee('Cliente Editado Dusk');
        });
    }

    public function test_delete_cliente(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'admin@test.com')->first();
            $cliente = \App\Models\Cadastro::factory()->create(['nome' => 'To Delete']);

            $browser->loginAs($user)
                ->visit('/admin/cadastros')
                // Filter or search to ensure visibility
                ->type('tableSearch', 'To Delete')
                ->pause(1000)
                ->waitForText('To Delete')
                ->click('button[title="Excluir"]') // Using the standardized tooltip if present, or rely on table action
                ->waitForText('Tem certeza', 5)
                ->press('Confirmar') // or 'Excluir' depending on locale
                ->waitUntilMissingText('To Delete', 10);
        });
    }
}
