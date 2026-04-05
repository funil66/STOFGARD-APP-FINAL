<?php

namespace Tests\Feature\Observers;

use App\Models\Cadastro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CadastroObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastro_creation_sends_notification_to_admins()
    {
        $admin = User::factory()->create();
        
        $cadastro = Cadastro::factory()->create(['tipo' => 'cliente']);
        
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);
    }
}
