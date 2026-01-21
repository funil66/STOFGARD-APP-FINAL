<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleClienteFeatureCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Feature flags removed');
    }

    public function test_enable_feature_for_single_cliente()
    {
        $cliente = Cliente::factory()->create();

        $this->artisan('cliente:feature', [
            'feature' => 'beta_feature_x',
            '--client' => $cliente->id,
            '--enable' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertTrue($cliente->fresh()->hasFeature('beta_feature_x'));
    }

    public function test_disable_feature_for_all_clients()
    {
        Cliente::factory()->count(3)->create();

        $this->artisan('cliente:feature', [
            'feature' => 'beta_feature_x',
            '--all' => true,
            '--enable' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertTrue(Cliente::first()->fresh()->hasFeature('beta_feature_x'));

        $this->artisan('cliente:feature', [
            'feature' => 'beta_feature_x',
            '--all' => true,
            '--disable' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFalse(Cliente::first()->fresh()->hasFeature('beta_feature_x'));
    }
}
