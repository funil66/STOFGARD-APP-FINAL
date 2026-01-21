<?php

namespace Tests\Feature;

use App\Filament\Resources\FinanceiroResource\Pages\CreateFinanceiro;
use App\Models\Cliente;
use App\Models\Financeiro as FinanceiroModel;
use App\Models\Parceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceiroResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_financeiro_with_parceiro_cadastro_sets_parceiro_and_cadastro()
    {
        $parceiro = Parceiro::create(['nome' => 'Parceiro Test', 'tipo' => 'loja', 'registrado_por' => 'test']);

        Livewire::test(CreateFinanceiro::class)
            ->set('data.tipo', 'entrada')
            ->set('data.descricao', 'Recebimento Parceiro')
            ->set('data.valor', 250.00)
            ->set('data.data', now()->format('Y-m-d'))
            ->set('data.cadastro_id', 'parceiro_' . $parceiro->id)
            ->call('create');

        $this->assertDatabaseHas('financeiros', [
            'cadastro_id' => 'parceiro_' . $parceiro->id,
            'parceiro_id' => $parceiro->id,
            'valor' => 250.00,
        ]);
    }

    public function test_create_financeiro_with_cliente_cadastro_sets_cliente_and_cadastro()
    {
        $cliente = Cliente::factory()->create();

        Livewire::test(CreateFinanceiro::class)
            ->set('data.tipo', 'entrada')
            ->set('data.descricao', 'Recebimento Cliente')
            ->set('data.valor', 150.00)
            ->set('data.data', now()->format('Y-m-d'))
            ->set('data.cadastro_id', 'cliente_' . $cliente->id)
            ->call('create');

        $this->assertDatabaseHas('financeiros', [
            'cadastro_id' => 'cliente_' . $cliente->id,
            'cliente_id' => $cliente->id,
            'valor' => 150.00,
        ]);
    }
}
