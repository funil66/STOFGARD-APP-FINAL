<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_getCadastroAttribute_returns_cliente()
    {
        $cliente = Cliente::factory()->create();

        $agenda = Agenda::create([
            'titulo' => 'Visita Teste',
            'descricao' => 'Descrição',
            'data_hora_inicio' => now(),
            'data_hora_fim' => now()->addHour(),
            'tipo' => 'visita',
            'cadastro_id' => 'cliente_' . $cliente->id,
            'criado_por' => 'TT',
        ]);

        $this->assertNotNull($agenda->cadastro);
        $this->assertEquals($cliente->id, $agenda->cadastro->id);
    }

    public function test_agenda_getCadastroAttribute_returns_parceiro()
    {
        $parceiro = \App\Models\Parceiro::create(['nome' => 'P1', 'tipo' => 'loja', 'registrado_por' => 'test']);

        $agenda = Agenda::create([
            'titulo' => 'Visita Parceiro',
            'descricao' => 'Descrição',
            'data_hora_inicio' => now(),
            'data_hora_fim' => now()->addHour(),
            'tipo' => 'visita',
            'cadastro_id' => 'parceiro_' . $parceiro->id,
            'criado_por' => 'TT',
        ]);

        $this->assertNotNull($agenda->cadastro);
        $this->assertEquals($parceiro->id, $agenda->cadastro->id);
    }
}
