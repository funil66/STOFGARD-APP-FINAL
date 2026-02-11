<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DataVerificationTest extends DuskTestCase
{
    /**
     * Verify that the database has been populated with test data.
     * This test assumes `php artisan db:seed` has been run or will be run.
     * Note: Dusk usually uses DatabaseTruncation. To test population, we might need
     * to run the seeder inside the test or ensure the env is set up.
     * For this 'Test populating the DB' request, we will call the seeder directly if empty,
     * or verify existing data.
     */
    public function test_data_population_and_browsing(): void
    {
        $this->browse(function (Browser $browser) {
            // Log in as Admin
            $user = User::where('email', 'admin@stofgard.com')->first();

            if (!$user) {
                // If user doesn't exist, it means DB might be empty or wiped.
                // We should probably fail or seed here. 
                // But Dusk tests usually run in a transaction or truncation.
                // Let's rely on the user running `php artisan db:seed` OR
                // we can call artisan here.
                \Illuminate\Support\Facades\Artisan::call('db:seed');
                $user = User::where('email', 'admin@stofgard.com')->firstOrFail();
            }

            $browser->loginAs($user)
                ->visit('/admin');

            // 1. Verify Cadastros
            $browser->visit('/admin/cadastros')
                ->waitForText('Cadastros')
                ->assertSee('Cadastros')
                // Check for realistic names from Seeder (faker) or just count
                // The seeder creates 25 clientes PF, 15 PJ, etc.
                // We can check if table is not empty.
                ->waitFor('.fi-ta-record', 10);

            // 2. Verify Orçamentos
            $browser->visit('/admin/orcamentos')
                ->waitForText('Orçamentos')
                ->waitFor('.fi-ta-record', 10);

            // 3. Verify Ordens de Serviço
            $browser->visit('/admin/ordem-servicos')
                ->waitForText('Ordem de Serviços') // Check correct title
                ->waitFor('.fi-ta-record', 10);

            // 4. Verify Financeiro
            $browser->visit('/admin/financeiros')
                ->waitForText('Financeiro')
                ->waitFor('.fi-ta-record', 10);

            // 5. Verify Estoque
            $browser->visit('/admin/estoques')
                ->waitForText('Estoque')
                ->waitFor('.fi-ta-record', 10);
        });
    }
}
