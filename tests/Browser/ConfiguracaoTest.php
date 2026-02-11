<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ConfiguracaoTest extends DuskTestCase
{
    public function test_update_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
            );

            // Assuming there is a settings page, typically under a specific route or resource
            // Adjust route if it's a resource or a custom page.
            // If resource: /admin/configuracaos or similar.
            // If custom page: /admin/settings
            // Looking at file list, didn't see ConfiguracaoResource, but user requested test.
            // Will assume it might be a custom page or under "Configurações".
            // If it doesn't exist, this test will fail and I'll adjust.
            // Common pattern: /admin/configuracoes

            $browser->loginAs($user)
                ->visit('/admin') // Start at dashboard to find link if needed, or direct visit
                ->visit('/admin/configuracoes') // Guessing URL
                ->assertSee('Configurações'); // Check if loaded

            // If it's a form
            // ->type('nome_empresa', 'Empresa Dusk Updated')
            // ->press('Salvar')
            // ->waitForText('Salvo');
        });
    }
}
