<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CadastroTest extends DuskTestCase
{
    public function test_cadastro_index_e_create_carregam(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/cadastros');
            $this->assertPanelPageLoads($browser, '/portal/cadastros/create');
        });
    }

    public function test_cadastro_view_route_pattern_carrega(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/cadastros');
        });
    }
}
