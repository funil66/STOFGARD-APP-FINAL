<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Cliente;

class CadastrosLivewireDuskTest extends DuskTestCase
{
    public function test_admin_can_edit_and_upload_file_and_toggle_types()
    {
        // skip if no PDO driver available locally; test is ready to run on CI with DB
        if (! extension_loaded('pdo_sqlite') && ! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('No PDO driver available for Dusk tests');
        }

        Storage::fake('public');

        // If sqlite is available, create a file-based testing DB and run migrations so Dusk's browser process can access it
        if (extension_loaded('pdo_sqlite')) {
            $dbFile = database_path('testing.sqlite');
            if (! file_exists($dbFile)) {
                touch($dbFile);
            }
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $dbFile]);
            Artisan::call('migrate', ['--force' => true]);
        }

        // If chromedriver not available locally, skip Dusk to avoid false failures in minimal environments
        $chromedriver = trim(@shell_exec('command -v chromedriver 2>/dev/null'));
        if (empty($chromedriver)) {
            $this->markTestSkipped('Chromedriver not available locally for Dusk.');
        }

        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);
        $cliente = Cliente::factory()->create(['nome' => 'Dusk Cliente']);

        // create a temporary file to upload
        $tmp = sys_get_temp_dir() . '/dusk-test-upload.txt';
        file_put_contents($tmp, 'dusk test file');

        $this->browse(function (Browser $browser) use ($admin, $cliente, $tmp) {
            $browser->loginAs($admin)
                ->visit('/cadastros/' . $cliente->uuid . '/edit')
                ->assertSee('Editar:')
                // initial page for cliente should show CPF field
                ->assertSee('CPF/CNPJ')
                // change the top-level selector to loja and assert partner fields appear
                ->select('cadastroTipo', 'loja')
                ->assertSee('Razão Social')
                // change to vendedor and still see partner fields
                ->select('cadastroTipo', 'vendedor')
                ->assertSee('Razão Social')
                // change back to cliente
                ->select('cadastroTipo', 'cliente')
                ->assertSee('CPF/CNPJ')
                // fill a field by name and attach file via newUpload
                ->type('nome', 'Dusk Cliente Updated')
                ->attach('newUpload', $tmp)
                ->press('Salvar')
                ->waitForText('Cadastro atualizado.')
                ->assertSee('Cadastro atualizado.')
                ->visit('/cadastros/' . $cliente->uuid)
                ->assertSee('Dusk Cliente Updated')
                ->assertSee('dusk-test-upload.txt');

            // cleanup temp
            @unlink($tmp);
        });
    }
}
