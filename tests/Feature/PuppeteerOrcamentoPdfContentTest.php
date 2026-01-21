<?php

namespace Tests\Feature;

use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PuppeteerOrcamentoPdfContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_pdf_contains_expected_sections()
    {
        $user = User::factory()->create();
        $orc = Orcamento::factory()->create([
            'valor_total' => 300.00,
            'forma_pagamento' => 'pix',
        ]);

        $orc->itens()->create([
            'descricao_item' => 'Teste Item Conteudo',
            'unidade_medida' => 'm2',
            'quantidade' => 1,
            'valor_unitario' => 300.00,
        ]);
        $orc->calcularTotal();
        $orc->save();

        $this->artisan('stofgard:render-orcamento-html', ['orcamentoId' => $orc->id])->assertExitCode(0);
        $htmlPath = storage_path("debug/orcamento-{$orc->id}.html");
        $pdfPath = storage_path("debug/orcamento-{$orc->id}-puppeteer.pdf");

        $node = env('NODE_BINARY', 'node');
        $script = base_path('scripts/generate-pdf.js');

        // Skip the test when Node is not available or not a real Node binary
        try {
            $versionProc = new \Symfony\Component\Process\Process([$node, '--version']);
            $versionProc->setTimeout(10);
            $versionProc->run();
            $versionOut = trim($versionProc->getOutput() ?: $versionProc->getErrorOutput());
            if (! preg_match('/^v\d+\./', $versionOut)) {
                $this->markTestSkipped('Node binary not found or not a node executable; skipping Puppeteer integration test.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Node binary not found; skipping Puppeteer integration test.');
        }

        $process = new \Symfony\Component\Process\Process([$node, $script, $htmlPath, $pdfPath]);
        $process->setTimeout(120);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        $this->assertFileExists($pdfPath);

        // Extract text with `strings` and assert key phrases appear
        $text = shell_exec("strings " . escapeshellarg($pdfPath));

        $this->assertStringContainsString('STOFGARD', $text);
        $this->assertStringContainsString('DADOS DO CLIENTE', $text);
        $this->assertStringContainsString('ITENS DO ORÇAMENTO', $text);
        $this->assertStringContainsString('VALOR TOTAL', $text);
        $this->assertStringContainsString('PIX', $text);
        $this->assertStringContainsString('Validade', $text);

        @unlink($htmlPath);
        @unlink($pdfPath);
    }
}
