<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CadastroFlowTest extends DuskTestCase
{
    public function test_criar_novo_parceiro()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();

            $browser->loginAs($user)
                ->visit('/admin/cadastros/create')
                ->waitForText('Novo Cadastro')
                ->type('nome', 'Parceiro Dusk Teste')
                // ->type('cpf_cnpj', '00000000000') // Se tiver validação, pode falhar
                ->select('tipo', 'cliente') // Ajustar conforme opções
                ->click('button[type="submit"]') // Ou o texto do botão
                ->waitForText('Criado com sucesso') // Ou redirecionamento
            ;

            // Verifica na listagem
            $browser->visit('/admin/cadastros')
                ->waitForText('Parceiro Dusk Teste');
        });
    }
}
