<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinanceiroTest extends DuskTestCase
{
    public function test_create_receita(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
            );

            $browser->loginAs($user)
                ->visit('/admin/financeiros')
                ->waitForText('Financeiros')
                ->click('a[href*="/create"]')
                ->waitForText('Novo Financeiro')
                ->select('tipo', 'entrada')
                ->type('descricao', 'Receita Teste Dusk')
                ->type('valor', '500.00')
                ->type('data_vencimento', now()->format('Y-m-d'))
                ->press('Criar')
                ->waitForText('Criado', 10)
                ->assertSee('Receita Teste Dusk');
        });
    }

    public function test_financeiro_filters(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'admin@test.com')->first();

            // Create dummy data
            \App\Models\Financeiro::create([
                'descricao' => 'Despesa Filter Check',
                'tipo' => 'saida',
                'valor' => 100.00,
                'data_vencimento' => now(),
                'status' => 'pendente'
            ]);

            $browser->loginAs($user)
                ->visit('/admin/financeiros')
                ->waitForText('Despesa Filter Check')
                // Open Filter (Filament standard filter icon or text)
                ->click('button[title*="Filter"]') // Generic selector, might need adjustment
                ->pause(500)
                // Select Type 'Saída'
                ->select('tableFilters[tipo][value]', 'saida') // Generic selector guess
                ->pause(1000)
                ->assertSee('Despesa Filter Check');
        });
    }
}
