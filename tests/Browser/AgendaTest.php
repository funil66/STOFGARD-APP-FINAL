<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AgendaTest extends DuskTestCase
{
    public function test_index_agenda(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/agendas');
        });
    }

    public function test_create_agenda_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/agendas/create');
        });
    }
}
