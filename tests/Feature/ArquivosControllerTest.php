<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Cliente;

class ArquivosControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_and_delete_file()
    {
        Storage::fake('public');

        // create a fake file
        Storage::disk('public')->put('clientes-arquivos/test-file.txt', 'hello');

        $cliente = Cliente::factory()->create([
            'arquivos' => ['clientes-arquivos/test-file.txt'],
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        // Download (open)
        $model = base64_encode(get_class($cliente));
        $path = base64_encode('clientes-arquivos/test-file.txt');

        $response = $this->actingAs($admin)->get(route('admin.files.download', ['model' => $model, 'record' => $cliente->getKey(), 'path' => $path]) . '?download=1');
        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('content-disposition'));

        // Delete (signed URL)
        $deleteUrl = \Illuminate\Support\Facades\URL::signedRoute('admin.files.delete', ['model' => $model, 'record' => $cliente->getKey(), 'path' => $path]);

        $delResponse = $this->actingAs($admin)->get($deleteUrl);
        $delResponse->assertRedirect();

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $cliente->refresh();

        $this->assertEmpty($cliente->arquivos ?: []);
        Storage::disk('public')->assertMissing('clientes-arquivos/test-file.txt');
    }
}
