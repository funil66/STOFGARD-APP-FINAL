<?php

namespace App\Jobs;

use App\Models\Orcamento;
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

    public function handle(): void
    {
        $orcamento = Orcamento::findOrFail($this->orcamentoId);

        Log::info("[PdfJob] Gerando PDF do orçamento #{$orcamento->numero}", [
            'orcamento_id' => $orcamento->id,
            'attempt' => $this->attempts(),
        ]);

        $path = "orcamentos/orcamento-{$orcamento->id}-" . now()->format('YmdHis') . '.pdf';

        $pdf = app(\App\Services\PdfService::class)->generate(
            'pdf.orcamento',
            ['orcamento' => $orcamento],
            "Orcamento-{$orcamento->id}.pdf",
            false // return builder/inline for base64
        );

        $content = base64_decode($pdf->base64());
        \Illuminate\Support\Facades\Storage::put($path, $content);

        Log::info("[PdfJob] PDF gerado e salvo em {$path}");

        if ($orcamento->user_id) {
            $user = \App\Models\User::find($orcamento->user_id);
            if ($user && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                \Filament\Notifications\Notification::make()
                    ->title("📄 PDF do Orçamento #{$orcamento->numero} Gerado!")
                    ->success()
                    ->body("O arquivo já está pronto para download ou envio.")
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->button()
                            ->url(asset("storage/{$path}"), shouldOpenInNewTab: true),
                    ])
                    ->sendToDatabase($user);
            }
        }

        if ($this->sendToEmail && $orcamento->cliente) {
            // Dispara um Job separado para envio de e-mail
            // para não bloquear o Job de PDF se o SMTP falhar.
            SendEmailNotificationJob::dispatch(
                email: $this->sendToEmail,
                subject: "Orçamento #{$orcamento->numero} - AUTONOMIA ILIMITADA",
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

        try {
            $orcamento = Orcamento::find($this->orcamentoId);
            if ($orcamento && $orcamento->user_id) {
                $user = \App\Models\User::find($orcamento->user_id);
                if ($user && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                    \Filament\Notifications\Notification::make()
                        ->title("❌ Erro na Geração do PDF")
                        ->danger()
                        ->body("Falha ao gerar o documento do orçamento #{$orcamento->numero}.")
                        ->sendToDatabase($user);
                }
            }
        } catch (\Throwable) {}
    }
}
