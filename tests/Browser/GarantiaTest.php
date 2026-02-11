<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\OrdemServico;
use App\Models\Cadastro;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GarantiaTest extends DuskTestCase
{
    public function test_garantia_list_visibility(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
            );

            // Create an OS with Warranty (Concluded)
            $cliente = Cadastro::factory()->create(['nome' => 'Cliente Garantia Dusk']);
            $os = OrdemServico::create([
                'cadastro_id' => $cliente->id,
                'data_abertura' => now()->subDays(10),
                'data_conclusao' => now()->subDays(5),
                'dias_garantia' => 90,
                'status' => 'concluida',
                'numero_os' => 'OS-WARRANTY-TEST',
                'criado_por' => $user->id,
            ]);

            $browser->loginAs($user)
                ->visit('/admin/garantias')
                ->waitForText('Garantias')
                ->assertSee('OS-WARRANTY-TEST')
                ->assertSee('Ativa'); // Status should be active
        });
    }
}
