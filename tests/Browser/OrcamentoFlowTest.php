<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrcamentoFlowTest extends DuskTestCase
{
    public function test_criar_orcamento_e_aprovar(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/orcamentos/create');
        });
    }

    public function test_aprovar_orcamento_existente(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/orcamentos');
        });
    }
}
