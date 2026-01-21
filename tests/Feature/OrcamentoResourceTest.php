<?php

namespace Tests\Feature;

use App\Filament\Resources\OrcamentoResource\Pages\CreateOrcamento;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrcamentoResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_orcamento_model_stores_cadastro_and_getCadastro_returns_model()
    {
        $cliente = Cliente::factory()->create();

        $cliente_for_db = Cliente::factory()->create();

        $orcamento = Orcamento::create([
            'numero_orcamento' => Orcamento::gerarNumeroOrcamento(),
            'data_orcamento' => now(),
            'data_validade' => now()->addDays(7),
            'cliente_id' => $cliente_for_db->id, // campo obrigatório na tabela
            'cadastro_id' => 'cliente_' . $cliente->id,
            'tipo_servico' => 'higienizacao',
            'descricao_servico' => 'Teste direto via model',
            'status' => 'pendente',
            'criado_por' => 'TT',
        ]);

        $this->assertDatabaseHas('orcamentos', [
            'id' => $orcamento->id,
            'cadastro_id' => 'cliente_' . $cliente->id,
        ]);

        $this->assertNotNull($orcamento->cadastro);
        $this->assertEquals($cliente->id, $orcamento->cadastro->id);
    }

    public function test_orcamento_model_accepts_parceiro_cadastro_and_getCadastro_returns_parceiro()
    {
        $parceiro = \App\Models\Parceiro::create(['nome' => 'P1', 'tipo' => 'loja', 'registrado_por' => 'test']);

        $cliente_for_db = Cliente::factory()->create();

        $orcamento = Orcamento::create([
            'numero_orcamento' => Orcamento::gerarNumeroOrcamento(),
            'data_orcamento' => now(),
            'data_validade' => now()->addDays(7),
            'cliente_id' => $cliente_for_db->id, // tabela exige cliente_id
            'cadastro_id' => 'parceiro_' . $parceiro->id,
            'tipo_servico' => 'consultoria',
            'descricao_servico' => 'Teste via model parceiro',
            'status' => 'pendente',
            'criado_por' => 'TT',
        ]);

        $this->assertDatabaseHas('orcamentos', [
            'id' => $orcamento->id,
            'cadastro_id' => 'parceiro_' . $parceiro->id,
        ]);

        $this->assertNotNull($orcamento->cadastro);
        $this->assertEquals($parceiro->id, $orcamento->cadastro->id);
    }
}
