<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GarantiaTest extends DuskTestCase
{
    public function test_garantia_list_visibility(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/configuracoes/garantias');
        });
    }
}
