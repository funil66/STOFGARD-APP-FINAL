<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AgendaFlowTest extends DuskTestCase
{
    public function test_criar_evento_agenda()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();

            $browser->loginAs($user)
                ->visit('/admin/agendas')
                ->waitForText('Calendário') // Assume que tem um título
                ->click('button[wire\\:click*="create"]') // Ajustar seletor
                ->waitForDialog()
                ->within('.filament-modal', function ($modal) {
                    $modal->type('titulo', 'Reunião Teste Dusk')
                        ->type('descricao', 'Descrição do evento')
                        ->clickButton('Salvar');
                })
                ->waitForText('Reunião Teste Dusk');
        });
    }
}
