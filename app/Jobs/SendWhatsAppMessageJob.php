<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job para preparar e registrar a mensagem de WhatsApp.
 *
 * O WhatsAppService gera deeplinks (wa.me) — o Job garante que o link seja
 * gerado de forma assíncrona e armazenado para exibição/disparo posterior.
 * Quando integrar com a API do WhatsApp Business, este Job será o ponto central.
 */
class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tentativas com backoff exponencial para suportar instabilidades. */
    public int $tries = 5;

    public int $timeout = 60;

    /**
     * Backoff (segundos): 30s → 1min → 2min → 5min → 10min
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
        public readonly ?int $orcamentoId = null,
        public readonly ?int $cadastroId = null,
    ) {
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        Log::info("[WhatsAppJob] Gerando link WhatsApp para {$this->phone}", [
            'cadastro_id' => $this->cadastroId,
            'orcamento_id' => $this->orcamentoId,
            'attempt' => $this->attempts(),
        ]);

        // Gera o link universal wa.me com mensagem pré-formatada
        $link = $whatsAppService->getLink($this->phone, $this->message);

        // Armazena na tabela whatsapp_messages para histórico e auditoria
        WhatsappMessage::create([
            'cadastro_id' => $this->cadastroId,
            'phone' => $this->phone,
            'message' => $this->message,
            'link' => $link,
            'orcamento_id' => $this->orcamentoId,
            'status' => 'queued',
        ]);

        Log::info("[WhatsAppJob] Link WhatsApp registrado com sucesso", ['link' => $link]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[WhatsAppJob] Falha ao processar mensagem WhatsApp para {$this->phone}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
