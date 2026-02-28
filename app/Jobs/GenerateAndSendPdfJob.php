<?php

namespace App\Jobs;

use App\Models\Orcamento;
use App\Services\PdfGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GenerateAndSendPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Máximo de tentativas — PDF pode falhar por timeout do Browserless.
     */
    public int $tries = 3;

    /**
     * Timeout maior pois a geração de PDF pode demorar.
     */
    public int $timeout = 120;

    /**
     * Backoff: 1min → 5min → 15min
     * Dá tempo ao Browserless de se recuperar se estiver sobrecarregado.
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        public readonly int $orcamentoId,
        public readonly ?string $sendToEmail = null,
        public readonly bool $sendViaPdf = true,
    ) {
    }

    public function handle(PdfGeneratorService $pdfService): void
    {
        $orcamento = Orcamento::findOrFail($this->orcamentoId);

        Log::info("[PdfJob] Gerando PDF do orçamento #{$orcamento->numero}", [
            'orcamento_id' => $orcamento->id,
            'attempt' => $this->attempts(),
        ]);

        $path = $pdfService->gerarESalvarOrcamento($orcamento);

        Log::info("[PdfJob] PDF gerado e salvo em {$path}");

        if ($this->sendToEmail && $orcamento->cliente) {
            // Dispara um Job separado para envio de e-mail
            // para não bloquear o Job de PDF se o SMTP falhar.
            SendEmailNotificationJob::dispatch(
                email: $this->sendToEmail,
                subject: "Orçamento #{$orcamento->numero} - STOFGARD",
                body: "Segue em anexo o orçamento #{$orcamento->numero}.",
                attachmentPath: $path,
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[PdfJob] Falha ao gerar PDF do orçamento #{$this->orcamentoId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
