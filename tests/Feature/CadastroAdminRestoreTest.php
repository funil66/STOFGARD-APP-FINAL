<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;
use App\Models\User;
use App\Filament\Resources\CadastroViewResource;

class CadastroAdminRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_index_shows_restore_bulk_action_when_there_are_trashed_records()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $c = Cliente::factory()->create(['nome' => 'Trash Me']);
        $c->delete();

        $this->actingAs($admin);

        $url = \App\Filament\Resources\CadastroViewResource::getUrl('index');

        $resp = $this->get($url);

        $resp->assertStatus(200);
        $resp->assertSee('Restaurar selecionados');
    }
}
