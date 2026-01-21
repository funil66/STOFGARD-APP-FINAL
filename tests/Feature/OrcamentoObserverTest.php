<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrcamentoObserverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_ordem_servico_and_agenda_when_orcamento_is_created_as_aprovado()
    {
        $cliente = Cliente::factory()->create();
        $vendedor = User::factory()->create();

        $orcamento = Orcamento::factory()->create([
            'cliente_id' => $cliente->id,
            'criado_por' => $vendedor->id,
            'status' => 'aprovado',
            'data_servico_agendada' => now()->addDays(5),
        ]);

        $this->assertDatabaseHas('ordens_servico', [
            'orcamento_id' => $orcamento->id,
        ]);

        $ordemServico = OrdemServico::where('orcamento_id', $orcamento->id)->first();
        $this->assertNotNull($ordemServico);

        $this->assertDatabaseHas('agendas', [
            'orcamento_id' => $orcamento->id,
            'ordem_servico_id' => $ordemServico->id,
        ]);
    }

    #[Test]
    public function it_creates_ordem_servico_and_agenda_when_orcamento_is_updated_to_aprovado()
    {
        $cliente = Cliente::factory()->create();
        $vendedor = User::factory()->create();

        $orcamento = Orcamento::factory()->create([
            'cliente_id' => $cliente->id,
            'criado_por' => $vendedor->id,
            'status' => 'em_elaboracao',
            'data_servico_agendada' => now()->addDays(5),
        ]);

        $this->assertDatabaseMissing('ordens_servico', [
            'orcamento_id' => $orcamento->id,
        ]);

        $orcamento->update(['status' => 'aprovado']);

        $this->assertDatabaseHas('ordens_servico', [
            'orcamento_id' => $orcamento->id,
        ]);

        $ordemServico = OrdemServico::where('orcamento_id', $orcamento->id)->first();
        $this->assertNotNull($ordemServico);

        $this->assertDatabaseHas('agendas', [
            'orcamento_id' => $orcamento->id,
            'ordem_servico_id' => $ordemServico->id,
        ]);
    }

    #[Test]
    public function it_does_not_create_ordem_servico_if_status_is_not_aprovado()
    {
        $cliente = Cliente::factory()->create();
        $vendedor = User::factory()->create();

        $orcamento = Orcamento::factory()->create([
            'cliente_id' => $cliente->id,
            'criado_por' => $vendedor->id,
            'status' => 'em_elaboracao',
            'data_servico_agendada' => now()->addDays(5),
        ]);

        $this->assertDatabaseMissing('ordens_servico', [
            'orcamento_id' => $orcamento->id,
        ]);
    }
}
