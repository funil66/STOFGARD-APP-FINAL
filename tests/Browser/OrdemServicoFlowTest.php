<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrdemServicoFlowTest extends DuskTestCase
{
    public function test_receber_os_pagamento_parcial(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/ordem-servicos/create');
        });
    }
}
