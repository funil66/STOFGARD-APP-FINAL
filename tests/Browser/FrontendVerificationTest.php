<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FrontendVerificationTest extends DuskTestCase
{
    public function testAdminPanelRoutes(): void
    {
        $this->browse(function (Browser $browser) {
            $routes = [
                '/portal',
                '/portal/agendas',
                '/portal/cadastros',
                '/portal/categorias',
                '/portal/configuracoes',
                '/portal/almoxarifado/equipamentos',
                '/portal/almoxarifado/estoques',
                '/portal/financeiros',
                '/portal/configuracoes/garantias',
                '/portal/almoxarifado/lista-desejos',
                '/portal/notas-fiscais',
                '/portal/orcamentos',
                '/portal/ordem-servicos',
                '/portal/almoxarifado/produtos',
                '/portal/configuracoes/tabela-precos',
                '/portal/tarefas',
                '/portal/relatorios',
            ];

            foreach ($routes as $route) {
                $this->assertPanelPageLoads($browser, $route);
            }
        });
    }

    public function testResponsiveLayout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPanelPageLoads($browser, '/portal');

            $browser->resize(375, 812);
            $browser->pause(500);
            $browser->assertSourceHas('<html');

            $browser->resize(768, 1024);
            $browser->pause(500);
            $browser->assertSourceHas('<html');

            $browser->resize(1920, 1080);
            $browser->pause(500);
            $browser->assertSourceHas('<html');
        });
    }
}
