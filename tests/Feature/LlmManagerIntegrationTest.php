<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Services\LlmManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmManagerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_uses_default_provider_for_all_clients()
    {
        $cliente = Cliente::factory()->create();

        $manager = $this->app->make(LlmManager::class);

        $resp = $manager->generateForCliente($cliente, 'Olá Mundo');

        $this->assertStringStartsWith('default_provider_response:', $resp);
    }
}
