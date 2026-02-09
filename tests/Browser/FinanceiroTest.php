<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinanceiroTest extends DuskTestCase
{
    /**
     * Test financeiro index page.
     */
    public function test_index_financeiro(): void
    {
        $this->browse(function (Browser $browser) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Test',
                    'password' => bcrypt('password'),
                    'is_admin' => true,
                ]
            );

            $browser->loginAs($user)
                ->visit('/admin/financeiros')
                ->assertSee('Financeiros')
                ->assertSee('Nova Transação');
        });
    }

    /**
     * Test create financeiro.
     */
    public function test_create_financeiro(): void
    {
        $this->browse(function (Browser $browser) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Test',
                    'password' => bcrypt('password'),
                    'is_admin' => true,
                ]
            );

            // Create required dependencies
            $categoria = \App\Models\Categoria::firstOrCreate(
                ['nome' => 'Vendas', 'tipo' => 'financeiro_receita'],
                ['cor' => '#00ff00'] // Add required fields if any
            );
            $cliente = \App\Models\Cadastro::firstOrCreate(
                ['nome' => 'Cliente Teste', 'tipo' => 'cliente'],
                ['cpf_cnpj' => '00000000000']
            );

            $browser->loginAs($user)
                ->visit('/admin/financeiros/create')
                ->pause(1000)
                ->storeSource('debug_financeiro_create')
                ->assertSee('Dados da Transação')
                // Fill native inputs using ID
                ->select('#data\\.tipo', 'entrada') // Escape dot in ID for CSS selector if needed, or use ID directly. Dusk select default uses name. try raw selector.
                // Actually Dusk select expects name. If name is missing, we can use script or click options.
                // But wait, the element has ID. ->select('#data.tipo', 'entrada') might work if logic allows ID.
                // Let's try select using the ID selector directly if supported, or fallback to clicking options if it was custom.
                // But seeing HTML, it IS native select.
                // However, the ID contains a dot. Dusk/Driver might have issues with dot in ID unless escaped.
                // Let's try type and generic interaction.
                ->type('#data\\.descricao', 'Teste Dusk')
                ->type('#data\\.descricao', 'Teste Dusk');

            // Set numeric and date values using script to avoid locale/format issues
            $dateValue = now()->addDays(5)->format('Y-m-d');
            $browser->script([
                "document.getElementById('data.valor').value = '123.45';",
                "document.getElementById('data.valor').dispatchEvent(new Event('input', { bubbles: true }));",
                "document.getElementById('data.data_vencimento').value = '$dateValue';",
                "document.getElementById('data.data_vencimento').dispatchEvent(new Event('input', { bubbles: true }));",
                "document.getElementById('data.data_vencimento').dispatchEvent(new Event('change', { bubbles: true }));",
            ]);

            // Select Status (standard select)
            $browser->select('#data\\.status', 'pendente');

            // Handle Choices.js for Cadastro

            // Handle Choices.js for Cadastro
            $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//div[contains(@class, "choices") and .//select[@id="data.cadastro_id"]]'))->click();
            $browser->pause(500);
            $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//div[contains(@class, "choices__list--dropdown")]//div[contains(text(), "Cliente Teste")]'))->click();

            // Handle Choices.js for Categoria
            $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//div[contains(@class, "choices") and .//select[@id="data.categoria_id"]]'))->click();
            $browser->pause(500);
            $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//div[contains(@class, "choices__list--dropdown")]//div[contains(text(), "Vendas")]'))->click();

            $browser->pause(500);

            $browser->pause(500);

            // Use precise XPath to avoid clicking hidden Logout button (which is also type=submit)
            // The "Create" button is the primary action in the form
            $browser->driver->executeScript('arguments[0].scrollIntoView(true);', [
                $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.fi-form-actions button[type="submit"]')),
            ]);
            $browser->pause(500);
            $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.fi-form-actions button[type="submit"]'))->click();

            $browser->assertDontSee('campo é obrigatório')
                ->assertDontSee('The field is required')
                ->waitForText('Visualizar Financeiro', 10)
                ->assertSee('Teste Dusk')
                ->assertSee('123,45');
        });
    }
}
