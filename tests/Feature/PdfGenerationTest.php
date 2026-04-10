<?php

namespace Tests\Feature;

use App\Jobs\ProcessPdfJob;
use App\Models\Financeiro;
use App\Models\Orcamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orcamento_pdf_is_enqueued_and_redirects()
    {
        Queue::fake();

        $orcamento = Orcamento::factory()->create();

        $response = $this->actingAsSuperAdmin()->get(route('orcamento.pdf', $orcamento));

        $response->assertStatus(302);
        $this->assertDatabaseHas('pdf_generations', [
            'tipo' => 'orcamento',
            'modelo_id' => (string) $orcamento->id,
            'orcamento_id' => $orcamento->id,
            'status' => 'processing',
        ]);
        Queue::assertPushed(ProcessPdfJob::class);
    }

    public function test_financeiro_pdf_is_enqueued_and_redirects()
    {
        Queue::fake();

        $financeiro = Financeiro::factory()->create();

        $response = $this->actingAsSuperAdmin()->get(route('financeiro.pdf', $financeiro));

        $response->assertStatus(302);
        Queue::assertPushed(ProcessPdfJob::class);
    }

    public function test_financeiro_relatorio_mensal_is_enqueued_and_redirects()
    {
        Queue::fake();

        $response = $this->actingAsSuperAdmin()->get(route('financeiro.relatorio_mensal'));

        $response->assertStatus(302);
        Queue::assertPushed(ProcessPdfJob::class);
    }
}
