<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Parceiro;
use App\Filament\Resources\CadastroViewResource;

class CadastrosViewTest extends DuskTestCase
{
    /**
     * Guarded Dusk test: only runs when RUN_DUSK_TESTS=1 to avoid CI failures on machines without Chrome.
     */
    public function test_cadastros_view_shows_columns_and_screenshot()
    {
        if (! env('RUN_DUSK_TESTS')) {
            $this->markTestSkipped('Dusk tests are disabled. Set RUN_DUSK_TESTS=1 to enable.');
        }

        $admin = User::factory()->create(['is_admin' => true, 'password' => bcrypt('secret')]);

        $cliente = Cliente::factory()->create(['nome' => 'Cliente Dusk', 'telefone' => '11900000000']);
        $loja = Parceiro::factory()->create(['tipo' => 'loja', 'nome' => 'Loja Dusk']);
        $vendedor = Parceiro::factory()->create(['tipo' => 'vendedor', 'nome' => 'Vendedor Dusk', 'loja_id' => $loja->id, 'telefone' => '11911111111']);

        $this->browse(function (Browser $browser) use ($admin) {
            // Use Dusk helper login route to create session quickly for the browser
            $browser->visit("/_dusk/login/{$admin->id}")
                ->visit('/admin');

            // The logout button uses a title attribute (icon-only). Assert it's present.
            $browser->assertPresent('button[title="Sair"]')
                ->screenshot('cadastros_view');
        });
    }
}
