<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrcamentoTest extends DuskTestCase
{
    public function test_orcamento_index_e_create_carregam(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/orcamentos');
            $this->assertPanelPageLoads($browser, '/portal/orcamentos/create');
        });
    }

    public function test_orcamento_kanban_carrega(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal/orcamentos-kanban-board');
        });
    }
}
