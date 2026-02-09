<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FrontendVerificationTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function testAdminPanelRoutes(): void
    {
        $this->browse(function (Browser $browser) {
            // Login
            $browser->loginAs(User::find(1));

            $routes = [
                '/admin',
                '/admin/agendas',
                '/admin/cadastros',
                '/admin/categorias',
                '/admin/configuracoes',
                '/admin/almoxarifado/equipamentos',
                '/admin/almoxarifado/estoques', // almoxarifado/estoques might be the slug
                '/admin/financeiros',
                '/admin/configuracoes/garantias',
                '/admin/almoxarifado/lista-desejos',
                '/admin/notas-fiscais',
                '/admin/orcamentos',
                '/admin/ordem-servicos',
                '/admin/almoxarifado/produtos',
                '/admin/configuracoes/tabela-precos',
                '/admin/tarefas',
                '/admin/relatorios', // Pages
                '/admin/relatorios-avancados', // Pages
            ];

            foreach ($routes as $route) {
                // Visit URL
                $browser->visit($route)
                    ->assertPathIs($route);

                // Standardization Checks
                // 1. Sidebar exists
                $browser->assertPresent('.fi-sidebar');

                // 2. Header exists (topbar)
                $browser->assertPresent('.fi-topbar');

                // 3. Main content area exists
                $browser->assertPresent('main');

                // 4. No 404 or 500 error text visible
                $browser->assertDontSee('404')
                    ->assertDontSee('500')
                    ->assertDontSee('Not Found')
                    ->assertDontSee('Server Error');
            }
        });
    }

    public function testResponsiveLayout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::find(1))
                ->visit('/admin');

            // Test Mobile View
            $browser->resize(375, 812); // iPhone X
            $browser->pause(500);
            // Sidebar should be hidden or collapsible
            $browser->assertVisible('.fi-topbar');

            // Test Tablet View
            $browser->resize(768, 1024); // iPad
            $browser->pause(500);

            // Test Desktop View
            $browser->resize(1920, 1080);
            $browser->pause(500);
            $browser->assertVisible('.fi-sidebar');
        });
    }
}
