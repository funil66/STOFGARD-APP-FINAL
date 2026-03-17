<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EstoqueTest extends DuskTestCase
{
    public function test_estoque_index_e_create_carregam(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/almoxarifado/estoques');
            $this->assertPanelPageLoads($browser, '/portal/almoxarifado/estoques/create');
        });
    }
}
