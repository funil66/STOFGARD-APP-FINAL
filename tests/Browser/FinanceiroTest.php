<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinanceiroTest extends DuskTestCase
{
    public function test_financeiro_index_e_create_carregam(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/financeiros');
            $this->assertPanelPageLoads($browser, '/portal/financeiros/create');
        });
    }
}
