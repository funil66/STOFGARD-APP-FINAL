<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_configuracoes()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/admin/configuracoes');

        // Expect Forbidden (403)
        $response->assertForbidden();
    }

    public function test_admin_can_access_configuracoes()
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get('/admin/configuracoes');

        // Expect OK (200)
        $response->assertOk();
    }
}
