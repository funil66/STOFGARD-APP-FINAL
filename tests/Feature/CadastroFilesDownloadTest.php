<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;

class CadastroFilesDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_file_name_and_download_link()
    {
        $cliente = Cliente::factory()->create([
            'nome' => 'Cliente Arquivos',
            'arquivos' => ['pdfs/testfile.pdf'],
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        // debug: dump the created cliente from DB to /tmp
        $dbCliente = \App\Models\Cliente::find($cliente->id);
        file_put_contents('/tmp/cadastro_cliente_dump.txt', var_export($dbCliente?->toArray(), true));

        $response = $this->actingAs($admin)->get(\App\Filament\Resources\CadastroViewResource::getUrl('index'));

        $response->assertStatus(200);
        // Ensure the table contains the column header and our client row
        $response->assertSee('Arquivos');
        $response->assertSee('Cliente Arquivos');

        // The public show page displays uploaded files; check it renders the filename and a link
        $publicResponse = $this->get(route('cadastros.show', ['uuid' => $cliente->uuid]));
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('testfile.pdf');
    }
}
