<?php

namespace Tests\Feature;

use App\Filament\Pages\Relatorios;
use App\Models\Cliente;
use App\Models\Financeiro as FinanceiroModel;
use App\Models\OrdemServico;
use App\Models\Parceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RelatoriosTest extends TestCase
{
    use RefreshDatabase;

    public function test_servicos_report_filters_by_cadastro()
    {
        $cliente = Cliente::factory()->create();
        $other = Cliente::factory()->create();

        $os1 = new OrdemServico([
            'numero_os' => OrdemServico::gerarNumeroOS(),
            'tipo_servico' => 'higienizacao',
            'descricao_servico' => 'Serviço de Teste',
            'cadastro_id' => 'cliente_' . $cliente->id,
            'data_abertura' => now(),
            'status' => 'aberta',
            'valor_total' => 100.00,
            'criado_por' => 'test',
        ]);
        $os1->cliente_id = $cliente->id;
        $os1->save();

        $os2 = new OrdemServico([
            'numero_os' => OrdemServico::gerarNumeroOS(),
            'tipo_servico' => 'higienizacao',
            'descricao_servico' => 'Serviço de Teste',
            'cadastro_id' => 'cliente_' . $other->id,
            'data_abertura' => now(),
            'status' => 'aberta',
            'valor_total' => 200.00,
            'criado_por' => 'test',
        ]);
        $os2->cliente_id = $other->id;
        $os2->save();

        Livewire::test(Relatorios::class)
            ->set('data.relatorio', 'servicos')
            ->set('data.data_inicio', now()->subDay()->format('Y-m-d'))
            ->set('data.data_fim', now()->addDay()->format('Y-m-d'))
            ->set('data.cadastro_id', 'cliente_' . $cliente->id)
            ->call('gerarRelatorio')
            ->assertSet('dadosRelatorio.total', 1);
    }

    public function test_financeiro_report_filters_by_cadastro()
    {
        $cliente = Cliente::factory()->create();
        $parceiro = Parceiro::create(['nome' => 'Parceiro Test', 'tipo' => 'loja', 'registrado_por' => 'test']);

        FinanceiroModel::create([
            'descricao' => 'Recebimento Cliente',
            'tipo' => 'receita',
            'valor' => 150.00,
            'status' => 'pago',
            'data' => now(),
            'data_vencimento' => now(),
            'cliente_id' => $cliente->id,
            'cadastro_id' => 'cliente_' . $cliente->id,
        ]);

        FinanceiroModel::create([
            'descricao' => 'Recebimento Parceiro',
            'tipo' => 'receita',
            'valor' => 250.00,
            'status' => 'pago',
            'data' => now(),
            'data_vencimento' => now(),
            'parceiro_id' => $parceiro->id,
            'cadastro_id' => 'parceiro_' . $parceiro->id,
        ]);

        Livewire::test(Relatorios::class)
            ->set('data.relatorio', 'financeiro')
            ->set('data.data_inicio', now()->subDay()->format('Y-m-d'))
            ->set('data.data_fim', now()->addDay()->format('Y-m-d'))
            ->set('data.cadastro_id', 'parceiro_' . $parceiro->id)
            ->call('gerarRelatorio')
            ->assertSet('dadosRelatorio.receitas_total', 250.00);
    }

    public function test_clientes_report_respects_cadastro_filter()
    {
        $cliente = Cliente::factory()->create();
        $parceiro = Parceiro::create(['nome' => 'Parceiro Test', 'tipo' => 'loja', 'registrado_por' => 'test']);

        // Ensure ordens count for cliente
        $os = new OrdemServico([
            'numero_os' => OrdemServico::gerarNumeroOS(),
            'tipo_servico' => 'higienizacao',
            'descricao_servico' => 'Serviço de Teste',
            'cadastro_id' => 'cliente_' . $cliente->id,
            'data_abertura' => now(),
            'status' => 'aberta',
            'valor_total' => 100.00,
            'criado_por' => 'test',
        ]);
        $os->cliente_id = $cliente->id;
        $os->save();

        // Cliente filter
        Livewire::test(Relatorios::class)
            ->set('data.relatorio', 'clientes')
            ->set('data.cadastro_id', 'cliente_' . $cliente->id)
            ->call('gerarRelatorio')
            ->assertSet('dadosRelatorio.total', 1);

        // Parceiro filter
        Livewire::test(Relatorios::class)
            ->set('data.relatorio', 'clientes')
            ->set('data.cadastro_id', 'parceiro_' . $parceiro->id)
            ->call('gerarRelatorio')
            ->assertSet('dadosRelatorio.total', 1);
    }
}
