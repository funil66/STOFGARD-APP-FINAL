<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EstoqueTest extends DuskTestCase
{
    public function test_create_estoque_item(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
            );

            $browser->loginAs($user)
                ->visit('/admin/estoques')
                ->waitForText('Estoques')
                ->click('a[href*="/create"]')
                ->waitForText('Novo Estoque')
                ->type('item', 'Item Estoque Dusk')
                ->type('quantidade', '100')
                ->select('unidade', 'un') // Assuming select
                ->type('valor_venda', '50.00')
                ->type('valor_custo', '30.00')
                ->select('tipo', 'produto')
                ->press('Criar')
                ->waitForText('Criado', 10)
                ->assertSee('Item Estoque Dusk');
        });
    }

    public function test_edit_estoque_quantity(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'admin@test.com')->first();
            $item = \App\Models\Estoque::firstOrCreate(
                ['item' => 'Item Quantidade Dusk'],
                ['quantidade' => 10, 'unidade' => 'un', 'valor_venda' => 10.00, 'tipo' => 'produto']
            );

            $browser->loginAs($user)
                ->visit("/admin/estoques/{$item->id}/edit")
                ->waitForText('Editar Estoque')
                ->type('quantidade', '20') // Updating Qty
                ->press('Salvar')
                ->waitForText('Salvo', 10)
                ->assertInputValue('quantidade', '20');
        });
    }
}
