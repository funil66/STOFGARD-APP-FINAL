<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;
use App\Models\User;

class CadastroPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_cadastros()
    {
        Cliente::factory()->create(['nome' => 'Alice Test']);

        $resp = $this->get(route('cadastros.index'));

        $resp->assertStatus(200);
        $resp->assertSee('Alice Test');
    }

    public function test_show_displays_cadastro()
    {
        $c = Cliente::factory()->create(['nome' => 'Bob Test', 'email' => 'bob@example.com']);

        $resp = $this->get(route('cadastros.show', ['uuid' => $c->uuid]));

        $resp->assertStatus(200);
        $resp->assertSee('Bob Test');
        $resp->assertSee('bob@example.com');
    }

    public function test_edit_requires_auth_and_persists_changes()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $c = Cliente::factory()->create(['nome' => 'Carlos Test']);

        $resp = $this->get(route('cadastros.edit', ['uuid' => $c->uuid]));
        $resp->assertRedirect('/login');

        // Non-admin authenticated users should be forbidden
        $this->actingAs($user);
        $resp = $this->get(route('cadastros.edit', ['uuid' => $c->uuid]));
        $resp->assertStatus(403);

        // Admin can access and update
        $this->actingAs($admin);
        $resp = $this->get(route('cadastros.edit', ['uuid' => $c->uuid]));
        $resp->assertStatus(200);
        $resp->assertSee('Editar:');

        $update = $this->put(route('cadastros.update', ['uuid' => $c->uuid]), [
            'nome' => 'Carlos Updated',
        ]);

        $update->assertRedirect(route('cadastros.show', ['uuid' => $c->uuid]));

        $this->assertDatabaseHas('clientes', ['id' => $c->id, 'nome' => 'Carlos Updated']);
    }

    public function test_destroy_requires_auth_and_soft_deletes()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $c = Cliente::factory()->create(['nome' => 'ToDelete']);

        $resp = $this->delete(route('cadastros.destroy', ['uuid' => $c->uuid]));
        $resp->assertRedirect('/login');

        // Non-admin cannot delete
        $this->actingAs($user);
        $resp = $this->delete(route('cadastros.destroy', ['uuid' => $c->uuid]));
        $resp->assertStatus(403);

        // Admin can delete (soft-delete)
        $this->actingAs($admin);
        $resp = $this->delete(route('cadastros.destroy', ['uuid' => $c->uuid]));
        $resp->assertRedirect(route('cadastros.index'));

        $this->assertSoftDeleted('clientes', ['id' => $c->id]);
    }
}
