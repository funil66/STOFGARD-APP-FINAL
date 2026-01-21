<?php

namespace Tests\Feature;

use App\Filament\Resources\CadastroViewResource;
use App\Models\Cliente;
use App\Models\Parceiro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastrosViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastros_view_contains_clientes_parceiros_and_columns()
    {
        $cliente = Cliente::factory()->create(["nome" => 'Cliente Teste', 'telefone' => '123456789']);
        $loja = Parceiro::factory()->create(['tipo' => 'loja', 'nome' => 'Loja Teste', 'registrado_por' => 'UT']);
        $vendedor = Parceiro::factory()->create(['tipo' => 'vendedor', 'nome' => 'Vendedor Teste', 'loja_id' => $loja->id, 'telefone' => '987654321']);

        // Ensure cadastros view contains expected rows
        $rows = \DB::table('cadastros_view')->get();
        $this->assertTrue($rows->contains(fn($r) => $r->model === 'cliente' && $r->nome === 'Cliente Teste'));
        $this->assertTrue($rows->contains(fn($r) => $r->model === 'parceiro' && $r->nome === 'Loja Teste'));
        $this->assertTrue($rows->contains(fn($r) => $r->model === 'parceiro' && $r->nome === 'Vendedor Teste'));

        // Access Filament resource page as admin and check for columns and values
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
            ->get(CadastroViewResource::getUrl('index'))
            ->assertStatus(200)
            ->assertSee('Telefone')
            ->assertSee('Loja Teste')
            ->assertSee('987654321')
            ->assertSee('Editar')
            ->assertSee('Excluir');
    }
}
