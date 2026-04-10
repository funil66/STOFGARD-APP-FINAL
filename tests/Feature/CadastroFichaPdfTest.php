<?php

namespace Tests\Feature;

use App\Jobs\ProcessPdfJob;
use App\Models\Cadastro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CadastroFichaPdfTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_ficha_cadastral_pdf_without_signature_fields()
    {
        Queue::fake();

        // Criar um cadastro de teste
        $cadastro = Cadastro::factory()->create([
            'nome' => 'João da Silva',
            'tipo' => 'cliente',
        ]);

        // Testar se a rota do PDF enfileira geração
        $response = $this->actingAsSuperAdmin()->get("/cadastro/{$cadastro->id}/pdf");

        $response->assertStatus(302);
        Queue::assertPushed(ProcessPdfJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_pdf_template_without_signature_sections()
    {
        $cadastro = Cadastro::factory()->create([
            'nome' => 'Maria Santos',
            'tipo' => 'cliente',
            'telefone' => '16999887766',
            'email' => 'maria@example.com',
        ]);

        // Renderizar a view diretamente para verificar conteúdo
        $view = view('pdf.cadastro_ficha', [
            'cadastro' => $cadastro,
            'config' => (object) [],
        ])->render();

        // Verificar que os campos de assinatura foram removidos
        $this->assertStringNotContainsString('Assinatura do Responsável', $view);
        $this->assertStringNotContainsString('Data: ____/____/________', $view);
        $this->assertStringNotContainsString('assinatura-section', $view);
        $this->assertStringNotContainsString('assinatura-line', $view);

        // Verificar que os dados básicos ainda aparecem
        $this->assertStringContainsString('MARIA SANTOS', $view);
        $this->assertStringContainsString('16999887766', $view);
        $this->assertStringContainsString('maria@example.com', $view);
    }
}
