<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Throwable;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tentativas — SMTP pode ter instabilidades temporárias.
     */
    public int $tries = 4;

    public int $timeout = 30;

    /**
     * Backoff: 1min → 5min → 15min → 30min
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function __construct(
        public readonly string $email,
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $attachmentPath = null,
    ) {
    }

    public function handle(): void
    {
        Log::info("[EmailJob] Enviando e-mail para {$this->email}", [
            'subject' => $this->subject,
            'attempt' => $this->attempts(),
        ]);

        Mail::raw($this->body, function (Message $message) {
            $message
                ->to($this->email)
                ->subject($this->subject);

            if ($this->attachmentPath && file_exists(storage_path('app/' . $this->attachmentPath))) {
                $message->attach(storage_path('app/' . $this->attachmentPath));
            }
        });

        Log::info("[EmailJob] E-mail enviado com sucesso para {$this->email}");
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[EmailJob] Falha ao enviar e-mail para {$this->email}", [
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
