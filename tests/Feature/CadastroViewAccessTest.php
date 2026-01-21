<?php

namespace Tests\Feature;

use App\Filament\Resources\CadastroViewResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastroViewAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_is_admin_set_to_int_one_can_access()
    {
        // simulate a DB value set to integer 1 (not strict boolean true)
        $admin = User::factory()->create(['is_admin' => 1]);

        $this->actingAs($admin)
            ->get(CadastroViewResource::getUrl('index'))
            ->assertStatus(200);
    }
}
