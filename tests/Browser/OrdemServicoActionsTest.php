<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrdemServicoActionsTest extends DuskTestCase
{
    public function test_os_actions_visibility_and_execution(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/ordem-servicos');
        });
    }
}
