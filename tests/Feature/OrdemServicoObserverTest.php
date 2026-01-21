<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\OrdemServico;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class OrdemServicoObserverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_agenda_and_financeiro_when_os_is_created()
    {
        $cliente = Cliente::factory()->create();

        $os = OrdemServico::create([
            'numero_os' => OrdemServico::gerarNumeroOS(),
            'cliente_id' => $cliente->id,
            'tipo_servico' => 'higienizacao',
            'descricao_servico' => 'Teste',
            'data_abertura' => now(),
            'data_prevista' => now()->addDays(3),
            'status' => 'pendente',
            'valor_total' => 250.00,
            'criado_por' => 'test',
        ]);

        $this->assertDatabaseHas('agendas', ['ordem_servico_id' => $os->id]);
        $this->assertDatabaseHas('transacoes_financeiras', ['ordem_servico_id' => $os->id, 'tipo' => 'receita']);
    }
}
