<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Policies\CadastroPolicy;
use App\Models\User;
use App\Models\Cliente;

class CadastroPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_and_delete()
    {
        $policy = new CadastroPolicy();

        $admin = User::factory()->create(['is_admin' => true]);
        $cliente = Cliente::factory()->create();

        $this->assertTrue($policy->update($admin, $cliente));
        $this->assertTrue($policy->delete($admin, $cliente));
    }

    public function test_non_admin_cannot_update_or_delete()
    {
        $policy = new CadastroPolicy();

        $user = User::factory()->create(['is_admin' => false]);
        $cliente = Cliente::factory()->create();

        $this->assertFalse($policy->update($user, $cliente));
        $this->assertFalse($policy->delete($user, $cliente));
    }

    public function test_authenticated_user_can_download()
    {
        $policy = new CadastroPolicy();

        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->assertTrue($policy->download($user, $cliente));
    }
}
