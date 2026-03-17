<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinanceiroFlowTest extends DuskTestCase
{
    public function test_financeiro_index_carrega(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/financeiros');
        });
    }

    public function test_financeiro_create_carrega(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/financeiros/create');
        });
    }
}
