<?php

namespace Tests\Unit\Services;

use App\Models\Equipamento;
use App\Services\EquipamentoService;
use App\Models\ListaDesejo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Filament\Notifications\Notification;
use App\Models\User;

class EquipamentoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enviar_para_lista_desejos_cria_registro()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $equipamento = Equipamento::create([
            'nome' => 'Furadeira',
            'descricao' => 'Furadeira de impacto',
            'valor_aquisicao' => 250.00,
        ]);

        EquipamentoService::enviarParaListaDesejos($equipamento);

        $this->assertDatabaseHas('lista_desejos', [
            'nome' => 'Furadeira',
            'categoria' => 'equipamento',
            'preco_estimado' => 250.00,
            'status' => 'pendente',
        ]);
    }
}
