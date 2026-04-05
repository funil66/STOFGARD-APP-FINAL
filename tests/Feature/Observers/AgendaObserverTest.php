<?php

namespace Tests\Feature\Observers;

use App\Models\Agenda;
use App\Models\Cadastro;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgendaObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_creation_syncs_with_os()
    {
        $user = User::factory()->create();
        $cadastro = Cadastro::factory()->create();
        $os = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'orcamento_id' => null,
            'valor_total' => 1500.50,
            'descricao_servico' => 'Serviço teste',
            'data_prevista' => null,
            'criado_por' => $user->id,
        ]);
        
        $horaInicio = Carbon::now()->setHour(10)->setMinute(0)->setSecond(0);
        $agenda = Agenda::create([
            'titulo' => 'Reunião',
            'cadastro_id' => $cadastro->id,
            'ordem_servico_id' => $os->id,
            'data_hora_inicio' => $horaInicio,
            'data_hora_fim' => $horaInicio->copy()->addHour(),
            'tipo' => 'servico',
            'status' => 'agendado',
            'criado_por' => $user->id,
        ]);
        
        $os->refresh();
        $this->assertEquals($horaInicio->toDateTimeString(), $os->data_prevista->toDateTimeString());
    }
}
