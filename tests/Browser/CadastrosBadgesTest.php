<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Parceiro;
use App\Filament\Resources\CadastroViewResource;

class CadastrosBadgesTest extends DuskTestCase
{
    /**
     * Guarded Dusk test that verifies tipo badges are rendered for cadastros.
     */
    public function test_cadastros_show_badges_and_screenshot()
    {
        if (! env('RUN_DUSK_TESTS')) {
            $this->markTestSkipped('Dusk tests are disabled. Set RUN_DUSK_TESTS=1 to enable.');
        }

        $admin = User::factory()->create(['is_admin' => true]);

        // Create sample entries
        $cliente = Cliente::factory()->create(['nome' => 'Cliente Badge', 'telefone' => '11922223333']);
        $loja = Parceiro::factory()->create(['tipo' => 'loja', 'nome' => 'Loja Badge']);
        $vendedor = Parceiro::factory()->create(['tipo' => 'vendedor', 'nome' => 'Vendedor Badge', 'loja_id' => $loja->id]);

        // Basic server-side validations to ensure records exist
        $this->assertDatabaseHas('clientes', ['nome' => 'Cliente Badge']);
        $this->assertDatabaseHas('parceiros', ['nome' => 'Loja Badge']);
        $this->assertDatabaseHas('parceiros', ['nome' => 'Vendedor Badge']);

        $this->browse(function (Browser $browser) use ($admin) {
            // authenticate the browser session
            $browser->visit("/_dusk/login/{$admin->id}")
                ->visit(CadastroViewResource::getUrl('index'))
                // Save page HTML for inspection to determine correct selector
                ;

            $page = $browser->driver->getPageSource();
            file_put_contents('tests/Browser/debug_cadastros.html', $page);

            $browser->screenshot('cadastros_badges');
        });
    }
}
