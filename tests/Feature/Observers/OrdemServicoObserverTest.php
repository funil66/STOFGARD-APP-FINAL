<?php

namespace Tests\Feature\Observers;

use App\Models\OrdemServico;
use App\Models\Cadastro;
use App\Models\Financeiro;
use App\Models\Agenda;
use App\Models\Estoque;
use App\Models\User;
use App\Jobs\EnviarSolicitacaoAvaliacaoJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrdemServicoObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordem_servico_created_generates_financeiro_and_agenda()
    {
        $user = User::factory()->create();
        $cadastro = Cadastro::factory()->create();
        
        $os = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'orcamento_id' => null,
            'valor_total' => 1500.50,
            'descricao_servico' => 'Serviço teste',
            'data_prevista' => now()->addDays(2),
            'criado_por' => $user->id,
        ]);

        $os->refresh();

        $financeiro = Financeiro::where('ordem_servico_id', $os->id)->first();
        $this->assertNotNull($financeiro);
        $this->assertEquals(1500.50, $financeiro->valor);
        $this->assertEquals('entrada', $financeiro->tipo);

        $agenda = Agenda::where('ordem_servico_id', $os->id)->first();
        $this->assertNotNull($agenda);
        $this->assertEquals("Serviço - OS #{$os->numero_os}", $agenda->titulo);
    }

    public function test_ordem_servico_concluida_dispatches_solicitacao_avaliacao()
    {
        Queue::fake();

        $user = User::factory()->create();
        $cadastro = Cadastro::factory()->create();
        
        $os = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'orcamento_id' => null,
            'valor_total' => 1500.50,
            'descricao_servico' => 'Serviço teste',
            'data_prevista' => now()->addDays(2),
            'criado_por' => $user->id,
            'status' => 'execucao',
        ]);

        $financeiro = Financeiro::where('ordem_servico_id', $os->id)->first();
        $financeiro->update(['status' => 'pago']);

        $os->update(['status' => 'concluida']);

        Queue::assertPushed(EnviarSolicitacaoAvaliacaoJob::class);
    }
}
