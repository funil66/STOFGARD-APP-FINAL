<?php

namespace Tests\Feature;

use App\Jobs\ProcessPdfJob;
use App\Models\Cadastro;
use App\Models\Garantia;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\PdfGeneration;
use App\Models\User;
use App\Services\PdfQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;
use Tests\TestCase;

class PdfQueueGarantiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificado_garantia_displays_id_parceiro_when_present(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);

        $cadastro = Cadastro::factory()->create();
        $orcamento = Orcamento::factory()->create([
            'cadastro_id' => $cadastro->id,
        ]);

        $ordemServico = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'orcamento_id' => $orcamento->id,
            'status' => 'concluida',
            'tipo_servico' => 'servico',
            'descricao_servico' => 'Teste garantia com parceiro',
            'data_abertura' => now(),
            'data_conclusao' => now(),
            'criado_por' => $user->id,
            'dias_garantia' => 90,
            'id_parceiro' => 'PARC-1234',
        ]);

        $garantia = Garantia::create([
            'ordem_servico_id' => $ordemServico->id,
            'tipo_servico' => $ordemServico->tipo_servico,
            'data_inicio' => now(),
            'data_fim' => now()->addDays(90),
            'dias_garantia' => 90,
            'status' => 'ativa',
            'observacoes' => 'Teste de ID parceiro no certificado',
        ]);

        $html = view('pdf.certificado_garantia', [
            'garantia' => $garantia,
            'os' => $ordemServico->fresh(['cliente', 'itens', 'garantias']),
            'config' => (object) [],
        ])->render();

        $this->assertStringContainsString('ID Parceiro:', $html);
        $this->assertStringContainsString('PARC-1234', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
        $this->assertStringContainsString('QR Code de validação', $html);
    }

    public function test_it_registers_and_dispatches_garantia_pdf_with_parent_orcamento(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);

        $cadastro = Cadastro::factory()->create();
        $orcamento = Orcamento::factory()->create([
            'cadastro_id' => $cadastro->id,
        ]);

        $ordemServico = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'orcamento_id' => $orcamento->id,
            'status' => 'concluida',
            'tipo_servico' => 'servico',
            'descricao_servico' => 'Teste garantia',
            'data_abertura' => now(),
            'data_conclusao' => now(),
            'criado_por' => $user->id,
            'dias_garantia' => 90,
        ]);

        $garantia = Garantia::create([
            'ordem_servico_id' => $ordemServico->id,
            'tipo_servico' => $ordemServico->tipo_servico,
            'data_inicio' => now(),
            'data_fim' => now()->addDays(90),
            'dias_garantia' => 90,
            'status' => 'ativa',
            'observacoes' => 'Teste de fila',
        ]);

        PdfQueueService::enqueue(
            $garantia->id,
            'garantia',
            $user->id,
            '<html><body>ok</body></html>',
            $orcamento->id,
        );

        $this->assertDatabaseHas('pdf_generations', [
            'tipo' => 'garantia',
            'modelo_id' => (string) $garantia->id,
            'orcamento_id' => $orcamento->id,
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        Queue::assertPushed(ProcessPdfJob::class, function (ProcessPdfJob $job) use ($garantia) {
            $ref = new ReflectionClass($job);

            $modeloId = $ref->getProperty('modeloId');
            $modeloId->setAccessible(true);

            $tipo = $ref->getProperty('tipo');
            $tipo->setAccessible(true);

            $recordId = $ref->getProperty('recordId');
            $recordId->setAccessible(true);

            return $recordId->getValue($job) !== null
                && $tipo->getValue($job) === 'garantia'
                && (int) $modeloId->getValue($job) === $garantia->id;
        });
    }
}
