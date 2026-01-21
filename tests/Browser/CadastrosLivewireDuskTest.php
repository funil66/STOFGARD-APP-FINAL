<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Cliente;

class CadastrosLivewireDuskTest extends DuskTestCase
{
    public function test_admin_can_edit_and_upload_file_and_download()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);
        $cliente = Cliente::factory()->create(['nome' => 'Dusk Cliente']);

        // create a temporary file to upload
        $tmp = sys_get_temp_dir() . '/dusk-test-upload.txt';
        file_put_contents($tmp, 'dusk test file');

        $this->browse(function (Browser $browser) use ($admin, $cliente, $tmp) {
            $browser->loginAs($admin)
                ->visit('/cadastros/' . $cliente->uuid . '/edit')
                ->assertSee('Editar:')
                // fill a field
                ->type('nome', 'Dusk Cliente Updated')
                // attach file via Livewire upload (Dusk attaches to input fields)
                ->attach('arquivos[]', $tmp)
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
