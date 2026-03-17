<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrdemServicoTest extends DuskTestCase
{
    public function test_os_lifecycle_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/ordem-servicos');
            $this->assertPanelPageLoads($browser, '/portal/ordem-servicos/create');
        });
    }
}
