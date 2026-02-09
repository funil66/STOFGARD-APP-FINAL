<?php

namespace Tests\Feature;

use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_orcamento_records_created_by()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Criar orçamento mínimo válido
        // Ajuste os campos conforme o factory ou campos obrigatórios
        $orcamento = Orcamento::factory()->create();

        $this->assertEquals($user->id, $orcamento->created_by);
    }

    public function test_financeiro_records_updated_by()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Criado por user1
        $this->actingAs($user1);
        $financeiro = Financeiro::factory()->create();
        $this->assertEquals($user1->id, $financeiro->created_by);

        // Editado por user2
        $this->actingAs($user2);
        $financeiro->update(['descricao' => 'Updated Description']);

        $this->assertEquals($user2->id, $financeiro->updated_by);
    }
}
