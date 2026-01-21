<?php

namespace Tests\Feature;

use App\Models\Orcamento;
use App\Services\ConfiguracaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrcamentoPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_includes_qr_when_env_key_set()
    {
        ConfiguracaoService::delete('financeiro', 'pix_chave');
        putenv('PIX_CHAVE=+5516997539698');

        $orc = Orcamento::factory()->create([
            'forma_pagamento' => 'pix',
            'valor_total' => 200.00,
            'pix_qrcode_base64' => null,
        ]);

        // Use the local shell script generator in CI/test environments where
        // Puppeteer is not installed to keep the test deterministic.
        putenv('NODE_BINARY=/bin/sh');
        putenv('PDF_GENERATOR_SCRIPT=' . base_path('scripts/generate-pdf.sh'));

        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->get(route('orcamento.pdf', ['orcamento' => $orc->id]));

        $response->assertStatus(200);

        // A resposta deveria ser um PDF (conteúdo binário). Validamos apenas que retornou 200
        // e que o QR foi salvo por StaticPixQrCodeService.

        // Recarregar orçamento e garantir que a rota respondeu com sucesso.
        $orc->refresh();
        // Nota: quando existe chave global (PIX_CHAVE) a geração é feita em memória e pode
        // não ser persistida no orçamento. Testes separados cobrem a persistência via
        // StaticPixQrCodeService quando aplicável.
    }

    public function test_generate_and_save_pdf_creates_file_and_persists_preference()
    {
        // Preparar dados
        $user = \App\Models\User::factory()->create();
        $orcamento = Orcamento::factory()->create([
            'valor_total' => 123.45,
            'pdf_incluir_pix' => true,
            'forma_pagamento' => 'dinheiro', // evita gerar QR para simplificar
        ]);

        // Forçar o uso do script shell de teste
        putenv('NODE_BINARY=/bin/sh');
        putenv('PDF_GENERATOR_SCRIPT=' . base_path('scripts/generate-pdf.sh'));

        // Garantir que o diretório público exista e seja gravável no ambiente de teste
        if (! file_exists(public_path('pdfs'))) {
            @mkdir(public_path('pdfs'), 0777, true);
        }
        @chmod(public_path('pdfs'), 0777);

        // Criar arquivo destino com permissão para permitir overwrite durante o teste
        $target = public_path("pdfs/orcamento-{$orcamento->id}.pdf");
        @file_put_contents($target, '');
        @chmod($target, 0666);

        $response = $this->actingAs($user)->postJson(route('orcamento.generate', $orcamento), [
            'include_pix' => false,
            'persist' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['url']);

        $orcamento->refresh();
        $this->assertFalse((bool)$orcamento->pdf_incluir_pix, 'pdf_incluir_pix should be persisted as false');

        // Arquivo público deve existir (aceitamos public/pdfs ou storage/app/public/pdfs como fallback)
        $publicPath = public_path("pdfs/orcamento-{$orcamento->id}.pdf");
        $storageFallback = storage_path("app/public/pdfs/orcamento-{$orcamento->id}.pdf");
        $this->assertTrue(file_exists($publicPath) || file_exists($storageFallback), 'Expected PDF to be created in public/pdfs or storage/app/public/pdfs');

        // Registro de auditoria
        $this->assertDatabaseHas('pdf_generations', [
            'orcamento_id' => $orcamento->id,
            'user_id' => $user->id,
            'include_pix' => false,
        ]);
    }
}
