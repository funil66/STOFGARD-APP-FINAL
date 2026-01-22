<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\CadastroEdit;
use App\Models\Cliente;
use App\Models\Parceiro;

class CadastroEditLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_cadastro_kind_updates_internal_state()
    {
        $cliente = Cliente::factory()->create();

        Livewire::test(CadastroEdit::class, ['uuid' => $cliente->uuid])
            ->assertSet('type', 'cliente')
            ->assertSet('cadastroTipo', 'cliente')
            ->set('cadastroTipo', 'loja')
            ->assertSet('type', 'parceiro')
            ->assertSet('tipo', 'loja');
    }

    public function test_parceiro_initializes_with_tipo_and_switch_back_to_cliente()
    {
        $parceiro = Parceiro::factory()->create(['tipo' => 'vendedor']);

        Livewire::test(CadastroEdit::class, ['uuid' => $parceiro->uuid])
            ->assertSet('type', 'parceiro')
            ->assertSet('cadastroTipo', 'vendedor')
            ->set('cadastroTipo', 'cliente')
            ->assertSet('type', 'cliente');
    }
}
