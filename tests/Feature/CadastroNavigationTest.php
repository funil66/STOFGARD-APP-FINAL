<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class CadastroNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_cadastro_and_hides_legacy_menus()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200)
            ->assertSee('Cadastro')
            ->assertDontSee('Clientes')
            ->assertDontSee('Parceiros');
    }
}
