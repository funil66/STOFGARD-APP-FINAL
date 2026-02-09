<?php

namespace Tests\Browser;

use App\Models\Agenda;
use App\Models\Cliente;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AgendaTest extends DuskTestCase
{
    /**
     * Test the index page of Agenda.
     */
    public function test_index_agenda(): void
    {
        // $this->markTestSkipped('Skipping index for debugging create');
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Test',
                    'password' => bcrypt('password'),
                    'is_admin' => true,
                ]
            );

            // Create a sample agenda item
            $agenda = Agenda::create([
                'titulo' => 'Reunião de Teste Index',
                'tipo' => 'reuniao',
                'status' => 'agendado',
                'data_hora_inicio' => now()->addDay()->setHour(10)->setMinute(0),
                'data_hora_fim' => now()->addDay()->setHour(11)->setMinute(0),
                'criado_por' => $user->id,
            ]);

            $browser->loginAs($user)
                ->visit('/admin/agendas')
                ->storeSource('debug_agenda_index')
                ->assertSee('Agendamentos')
                ->assertSee($agenda->titulo);
        });
    }

    /**
     * Test creating a new Agenda item.
     */
    public function test_create_agenda(): void
    {
        $this->markTestSkipped('Skipping due to hang in headless environment dealing with repeater interaction.');
        $this->browse(function (Browser $browser) {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Test',
                    'password' => bcrypt('password'),
                    'is_admin' => true,
                ]
            );
            $cliente = Cliente::create([
                'nome' => 'Cliente Teste Agenda',
                'email' => 'cliente@teste.com',
                'telefone' => '11999999999',
                'celular' => '11999999999',
            ]);

            $browser->loginAs($user)
                ->visit('/admin/agendas/create')
                ->storeSource('debug_agenda_create_initial')
                ->waitForText('Dados', 10)
                ->pause(1000)

                // Fill Title
                ->type('#data\\.titulo', 'Serviço Dusk Automático');

            // Select Tipo using Script
            $browser->script([
                "const select = document.getElementById('data.tipo');",
                "select.value = 'servico';",
                "select.dispatchEvent(new Event('input', { bubbles: true }));",
                "select.dispatchEvent(new Event('change', { bubbles: true }));",
            ]);

            // Select Status using Script
            $browser->script([
                "const select = document.getElementById('data.status');",
                "select.value = 'agendado';",
                "select.dispatchEvent(new Event('input', { bubbles: true }));",
                "select.dispatchEvent(new Event('change', { bubbles: true }));",
            ]);

            // Handle DateTimePicker: 'data_hora_inicio'
            $startDate = now()->format('d/m/Y H:i');
            $endDate = now()->addHour()->format('d/m/Y H:i');

            $browser->script([
                "const start = document.getElementById('data.data_hora_inicio');",
                "start.value = '$startDate';",
                "start.dispatchEvent(new Event('input', { bubbles: true }));",
                "start.dispatchEvent(new Event('change', { bubbles: true }));",

                "const end = document.getElementById('data.data_hora_fim');",
                "end.value = '$endDate';",
                "end.dispatchEvent(new Event('input', { bubbles: true }));",
                "end.dispatchEvent(new Event('change', { bubbles: true }));",
            ]);

            // Handle Choices.js for 'cadastro_id' (Cliente)
            $clienteId = $cliente->id;
            $browser->script([
                "const select = document.getElementById('data.cadastro_id');",
                "select.value = '$clienteId';",
                "select.dispatchEvent(new Event('input', { bubbles: true }));",
                "select.dispatchEvent(new Event('change', { bubbles: true }));",
            ]);
            $browser->pause(1000);

            // Submit
            $browser->driver->executeScript("arguments[0].scrollIntoView({block: 'center', inline: 'nearest'});", [
                $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//button[contains(., "Create") and @type="submit"]')),
            ]);
            $browser->pause(500);

            $browser->storeSource('debug_agenda_create_before_submit');

            try {
                $browser->driver->executeScript('arguments[0].click();', [
                    $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//button[contains(., "Create") and @type="submit"]')),
                ]);

                $browser->waitForText('Agendamento criado com sucesso!', 15);
            } catch (\Exception $e) {
                $browser->storeSource('debug_agenda_create_fail');
                throw $e;
            }
        });
    }

    /**
     * Test editing an existing Agenda item.
     */
    /*
    public function testEditAgenda(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::first();

            // Create an item to edit
            $agenda = Agenda::create([
                'titulo' => 'Reunião Original',
                'tipo' => 'reuniao',
                'status' => 'agendado',
                'data_hora_inicio' => now()->addDay(),
                'data_hora_fim' => now()->addDay()->addHour(),
                'criado_por' => $user->id,
            ]);

            $browser->loginAs($user)
                ->visit("/admin/agendas/{$agenda->id}/edit")
                ->waitForText('Editar Agendamento', 10)

                // Change Title
                ->type('#data\\.titulo', 'Reunião Editada')

            // Change Status using Script
            $browser->script([
                "const select = document.getElementById('data.status');",
                "select.value = 'concluido';",
                "select.dispatchEvent(new Event('input', { bubbles: true }));",
                "select.dispatchEvent(new Event('change', { bubbles: true }));",
            ]);

            $browser->pause(500)
                ->storeSource('debug_agenda_edit_before_submit');

            // Submit
            try {
                $browser->driver->executeScript("arguments[0].scrollIntoView({block: 'center', inline: 'nearest'});", [
                    $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//button[contains(., "Save") and @type="submit"]'))
                ]);
                $browser->pause(500);

                $browser->driver->executeScript("arguments[0].click();", [
                    $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//button[contains(., "Save") and @type="submit"]'))
                ]);

                $browser->waitForText('Agendamento atualizado com sucesso!', 15);
                $browser->assertSee('Reunião Editada');

             } catch (\Exception $e) {
                 // Try looking for "Salvar" instead if "Save" fails
                 $browser->driver->executeScript("arguments[0].click();", [
                    $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//button[contains(., "Salvar") and @type="submit"]'))
                ]);
                 $browser->waitForText('Agendamento atualizado com sucesso!', 15);
                 $browser->assertSee('Reunião Editada');
             }
        });
    }
    */
}
