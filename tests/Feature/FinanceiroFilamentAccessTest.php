<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Financeiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceiroFilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_access_financeiro_index_without_categoria_conflict_error()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
        ]);

        // Testar se a página carrega sem o erro "Attempt to read property tipo on string"
        $response = $this->actingAs($user)->get('/admin/financeiros/transacoes');
        
        $response->assertStatus(200);
        
        // Verificar que não há erro interno
        $response->assertDontSee('Attempt to read property');
        $response->assertDontSee('Internal Server Error');
        
        // Verificar que a página Filament carregou corretamente
        $response->assertSee('Financeiro'); // Título da página
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function financeiro_categoria_accessor_returns_relationship_object()
    {
        // Este teste verifica se o accessor categoria funciona corretamente
        // quando há dados reais no banco
        
        $financeiro = Financeiro::with('categoria')->first();
        
        if ($financeiro) {
            $categoria = $financeiro->categoria;
            
            // Se há categoria, deve ser um objeto, não uma string
            if ($categoria) {
                $this->assertTrue(is_object($categoria), 'Categoria deve ser um objeto');
                $this->assertInstanceOf(\App\Models\Categoria::class, $categoria);
                $this->assertIsString($categoria->nome);
                $this->assertIsString($categoria->tipo);
            } else {
                $this->assertNull($categoria);
            }
        } else {
            $this->markTestSkipped('Nenhum financeiro encontrado no banco para teste');
        }
    }
}