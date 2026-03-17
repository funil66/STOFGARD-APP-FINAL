<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AgendaFlowTest extends DuskTestCase
{
    public function test_criar_evento_agenda(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/agendas/create');
        });
    }
}
