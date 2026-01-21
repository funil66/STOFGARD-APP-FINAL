<?php

namespace Tests\Feature;

use App\Filament\Resources\CadastroViewResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListCadastrosRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_cadastros_list_redirects_to_unified_view()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/cadastros')
            ->assertStatus(302)
            ->assertRedirect(CadastroViewResource::getUrl('index'));
    }
}
