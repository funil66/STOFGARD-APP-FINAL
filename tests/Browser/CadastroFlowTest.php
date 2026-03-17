<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CadastroFlowTest extends DuskTestCase
{
    public function test_criar_novo_parceiro(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/cadastros/create');
        });
    }
}
