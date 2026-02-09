<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceiroRoutingTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_access_new_financeiro_routes_correctly()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
        ]);

        // Testar se a nova rota de listagem funciona
        $response = $this->actingAs($user)->get('/admin/financeiros');
        $response->assertStatus(200);

        // Verificar que é a página correta (lista de transações financeiras)
        $response->assertSee('Transações Financeiras');

        // Testar se a rota de criação funciona  
        $response = $this->actingAs($user)->get('/admin/financeiros/create');
        $response->assertStatus(200);

        // Verificar que não há rotas legacy disponíveis
        $this->assertFalse(class_exists('App\Models\TransacaoFinanceira'));
        $this->assertFalse(class_exists('App\Filament\Resources\TransacaoFinanceiraResource'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function legacy_transacao_financeira_system_is_removed()
    {
        // Verificar que o sistema legacy foi completamente removido
        $this->assertFalse(file_exists(app_path('Models/TransacaoFinanceira.php')));
        $this->assertFalse(file_exists(app_path('Filament/Resources/TransacaoFinanceiraResource.php')));
        $this->assertFalse(file_exists(app_path('Filament/Resources/TransacaoFinanceiraResource')));

        // Verificar que a tabela foi removida
        $this->assertFalse(\Schema::hasTable('transacoes_financeiras'));
    }
}