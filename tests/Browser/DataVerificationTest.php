<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DataVerificationTest extends DuskTestCase
{
    public function test_data_population_and_browsing(): void
    {
        $this->browse(function (Browser $browser) {
            $routes = [
                '/portal/cadastros',
                '/portal/orcamentos',
                '/portal/ordem-servicos',
                '/portal/financeiros',
                '/portal/almoxarifado/estoques',
            ];

            foreach ($routes as $route) {
                $this->assertPanelPageLoads($browser, $route);
            }
        });
    }
}
