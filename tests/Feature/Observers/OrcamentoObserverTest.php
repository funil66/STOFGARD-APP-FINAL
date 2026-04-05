<?php

namespace Tests\Feature\Observers;

use App\Models\Orcamento;
use App\Models\Cadastro;
use App\Jobs\EnviarMagicLinkJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Enums\OrcamentoStatus;

class OrcamentoObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_orcamento_saving_generates_numero()
    {
        $cadastro = Cadastro::factory()->create();
        $orcamento = Orcamento::factory()->make([
            'cadastro_id' => $cadastro->id,
            'numero_orcamento' => null,
        ]);
        
        $this->assertNull($orcamento->numero_orcamento);
        $orcamento->save();
        
        $this->assertNotNull($orcamento->numero_orcamento);
    }

    public function test_orcamento_updated_to_aprovado_dispatches_magic_link_job()
    {
        Queue::fake();

        $cadastro = Cadastro::factory()->create();
        $orcamento = Orcamento::factory()->create([
            'cadastro_id' => $cadastro->id,
            'status' => OrcamentoStatus::Pendente->value,
        ]);

        $orcamento->update(['status' => OrcamentoStatus::Aprovado->value]);

        Queue::assertPushed(EnviarMagicLinkJob::class);
    }
}
