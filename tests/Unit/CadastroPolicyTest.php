<?php

namespace Tests\Unit;

use App\Models\Cadastro;
use App\Models\User;
use App\Policies\CadastroPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastroPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_and_delete()
    {
        $policy = new CadastroPolicy;

        $admin = User::factory()->create(['is_admin' => true]);
        $cadastro = Cadastro::factory()->create();

        $this->assertTrue($policy->update($admin, $cadastro));
        $this->assertTrue($policy->delete($admin, $cadastro));
    }

    public function test_non_admin_cannot_update_or_delete()
    {
        $policy = new CadastroPolicy;

        $user = User::factory()->create(['is_admin' => false]);
        $cadastro = Cadastro::factory()->create();

        $this->assertFalse($policy->update($user, $cadastro));
        $this->assertFalse($policy->delete($user, $cadastro));
    }

    public function test_authenticated_user_can_download()
    {
        $policy = new CadastroPolicy;

        $user = User::factory()->create();
        $cadastro = Cadastro::factory()->create();

        $this->assertTrue($policy->download($user, $cadastro));
    }
}
