<?php

namespace Tests\Feature;

use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orcamento_pdf_uses_service()
    {
        $user = User::factory()->create();
        $orcamento = Orcamento::factory()->create();

        // Mock PdfService
        $this->mock(PdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(function ($view, $data, $filename, $download) {
                    return $view === 'pdf.orcamento'
                        && str_contains($filename, 'Orcamento-')
                        && $download === true;
                })
                ->andReturn(response('PDF Content', 200, ['Content-Type' => 'application/pdf']));
        });

        $response = $this->actingAs($user)->get(route('orcamento.pdf', $orcamento));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_financeiro_pdf_uses_service()
    {
        $user = User::factory()->create();
        $financeiro = Financeiro::factory()->create();

        $this->mock(PdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(function ($view, $data, $filename) {
                    return $view === 'pdf.financeiro';
                })
                ->andReturn(response('PDF Content', 200, ['Content-Type' => 'application/pdf']));
        });

        $response = $this->actingAs($user)->get(route('financeiro.pdf', $financeiro));

        $response->assertOk();
    }

    public function test_financeiro_relatorio_mensal_uses_service()
    {
        $user = User::factory()->create();

        $this->mock(PdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(function ($view, $data, $filename) {
                    return $view === 'pdf.financeiro_mensal';
                })
                ->andReturn(response('PDF Content', 200, ['Content-Type' => 'application/pdf']));
        });

        $response = $this->actingAs($user)->get(route('financeiro.relatorio_mensal'));

        $response->assertOk();
    }
}
