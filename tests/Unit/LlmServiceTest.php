<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Services\LlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_choose_model_returns_default_when_no_models_configured()
    {
        $cliente = Cliente::factory()->create();

        $svc = new LlmService();

        $this->assertEquals('default', $svc->chooseModelForCliente($cliente));
    }

    public function test_is_model_available_for_cliente_returns_false_for_unknown_model()
    {
        $cliente = Cliente::factory()->create();

        $this->assertFalse((new LlmService())->isModelAvailableForCliente('claude_haiku_4_5', $cliente));
    }
}
