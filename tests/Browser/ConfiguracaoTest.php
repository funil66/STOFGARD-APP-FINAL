<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ConfiguracaoTest extends DuskTestCase
{
    public function test_update_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/configuracoes');
        });
    }
}
