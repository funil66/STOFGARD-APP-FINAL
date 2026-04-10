<?php

namespace Tests\Unit\Services;

use App\Jobs\ProcessPdfJob;
use App\Models\Orcamento;
use App\Models\User;
use App\Services\PdfQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;
use Tests\TestCase;

class PdfQueueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enqueue_cria_registro_em_processamento_e_despacha_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $orcamento = Orcamento::factory()->create();

        $pdfRecord = PdfQueueService::enqueue(
            modeloId: 123,
            tipo: 'garantia',
            userId: $user->id,
            htmlContent: '<html><body>teste</body></html>',
            orcamentoId: $orcamento->id,
        );

        $this->assertNotNull($pdfRecord);
        $this->assertDatabaseHas('pdf_generations', [
            'id' => $pdfRecord->id,
            'tipo' => 'garantia',
            'modelo_id' => '123',
            'user_id' => $user->id,
            'status' => 'processing',
            'orcamento_id' => $orcamento->id,
        ]);

        Queue::assertPushed(ProcessPdfJob::class, function (ProcessPdfJob $job) {
            $ref = new ReflectionClass($job);

            $tipo = $ref->getProperty('tipo');
            $tipo->setAccessible(true);

            $modeloId = $ref->getProperty('modeloId');
            $modeloId->setAccessible(true);

            $recordId = $ref->getProperty('recordId');
            $recordId->setAccessible(true);

            return $tipo->getValue($job) === 'garantia'
                && (int) $modeloId->getValue($job) === 123
                && !empty($recordId->getValue($job));
        });
    }

    public function test_enqueue_define_orcamento_id_automaticamente_quando_tipo_for_orcamento(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $orcamento = Orcamento::factory()->create();

        $pdfRecord = PdfQueueService::enqueue(
            modeloId: $orcamento->id,
            tipo: 'orcamento',
            userId: $user->id,
            htmlContent: '<html><body>orcamento</body></html>',
            orcamentoId: null,
        );

        $this->assertNotNull($pdfRecord);
        $this->assertDatabaseHas('pdf_generations', [
            'id' => $pdfRecord->id,
            'tipo' => 'orcamento',
            'modelo_id' => (string) $orcamento->id,
            'orcamento_id' => $orcamento->id,
            'status' => 'processing',
        ]);

        Queue::assertPushed(ProcessPdfJob::class);
    }
}
