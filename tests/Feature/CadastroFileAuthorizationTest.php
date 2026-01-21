<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Cliente;
use App\Models\User;

class CadastroFileAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_trying_to_download_file()
    {
        $cliente = Cliente::factory()->create([
            'nome' => 'Cliente Arquivos',
            'arquivos' => ['pdfs/testfile.pdf'],
        ]);

        $response = $this->get(route('cadastros.arquivo.download', ['uuid' => $cliente->uuid, 'path' => base64_encode('pdfs/testfile.pdf')]));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_download_existing_file()
    {
        Storage::fake('public');

        $path = 'pdfs/testfile.pdf';
        Storage::disk('public')->put($path, 'dummy content');

        $cliente = Cliente::factory()->create([
            'nome' => 'Cliente Arquivos',
            'arquivos' => [$path],
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cadastros.arquivo.download', ['uuid' => $cliente->uuid, 'path' => base64_encode($path)]));

        $response->assertStatus(200);
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('testfile.pdf', $disposition);
    }

    public function test_non_admin_cannot_delete_cadastro()
    {
        $cliente = Cliente::factory()->create(['nome' => 'ToDelete']);
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->delete(route('cadastros.destroy', ['uuid' => $cliente->uuid]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_admin_can_delete_cadastro()
    {
        $cliente = Cliente::factory()->create(['nome' => 'ToDelete']);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->delete(route('cadastros.destroy', ['uuid' => $cliente->uuid]));

        $response->assertRedirect(route('cadastros.index'));
        $this->assertSoftDeleted('clientes', ['id' => $cliente->id]);
    }
}
