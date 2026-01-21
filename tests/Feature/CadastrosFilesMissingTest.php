<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Cliente;

class CadastrosFilesMissingTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_file_shows_indicator_and_no_link_for_guest()
    {
        Storage::fake('public');

        $path = 'pdfs/missing.pdf';
        // do NOT put file in storage to simulate missing

        $c = Cliente::factory()->create(['nome' => 'Cliente Arquivos', 'arquivos' => [$path]]);

        $resp = $this->get(route('cadastros.show', ['uuid' => $c->uuid]));

        $resp->assertStatus(200);
        $resp->assertSee('missing.pdf');
        $resp->assertDontSee(route('cadastros.arquivo.download', ['uuid' => $c->uuid, 'path' => base64_encode($path)]));
        $resp->assertSee('Arquivo não encontrado');
    }
}
