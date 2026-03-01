<?php

namespace App\Actions;

use App\Models\Orcamento;
use App\Services\OrcamentoCalculator;
use App\Services\PdfGeneratorService;
use App\Jobs\GenerateAndSendPdfJob;
use App\Jobs\SendWhatsAppMessageJob;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;

/**
 * Ação: Enviar Orçamento ao Cliente (PDF + WhatsApp/E-mail).
 *
 * Orquestra a geração do PDF e o disparo das notificações de forma
 * totalmente assíncrona via Jobs. O usuário recebe feedback instantâneo.
 *
 * Uso:
 *   app(SendOrcamentoPdfAction::class)->execute($orcamento, via: 'whatsapp');
 *   app(SendOrcamentoPdfAction::class)->execute($orcamento, via: 'email');
 *   app(SendOrcamentoPdfAction::class)->execute($orcamento, via: 'both');
 */
class SendOrcamentoPdfAction
{
    public function __construct()
    {
    }

    /**
     * Enfileira a geração do PDF e notificação do cliente.
     *
     * @param  string  $via  'whatsapp' | 'email' | 'both'
     * @return array{queued: bool, message: string, whatsapp_link: string|null}
     */
    public function execute(Orcamento $orcamento, string $via = 'whatsapp'): array
    {
        $cliente = $orcamento->cliente;

        if (!$cliente) {
            Log::warning("[SendOrcamentoPdfAction] Orçamento #{$orcamento->numero} sem cliente vinculado.");

            return ['queued' => false, 'message' => 'Orçamento sem cliente vinculado.', 'whatsapp_link' => null];
        }

        $sendEmail = in_array($via, ['email', 'both']) && $cliente->email;
        $emailDestino = $sendEmail ? $cliente->email : null;

        // Job de PDF: gera via Browserless + envia e-mail se necessário
        GenerateAndSendPdfJob::dispatch(
            orcamentoId: $orcamento->id,
            sendToEmail: $emailDestino,
        )->onQueue('high');

        // Link WhatsApp (gerado de forma síncrona — é só texto, não bloqueia)
        $whatsappLink = null;
        if (in_array($via, ['whatsapp', 'both']) && $cliente->celular) {
            $pdfUrl = \Illuminate\Support\Facades\URL::signedRoute('orcamento.public_stream', ['orcamento' => $orcamento->id], now()->addDays(7));
            $phone = preg_replace('/[^0-9]/', '', $cliente->celular ?? '');
            $whatsappLink = "https://wa.me/55{$phone}?text=" . urlencode("Segue o link do orçamento #{$orcamento->numero}: {$pdfUrl}");

            // Armazena histórico da mensagem de forma assíncrona
            SendWhatsAppMessageJob::dispatch(
                phone: $cliente->celular,
                message: "Segue o link do orçamento #{$orcamento->numero}",
                orcamentoId: $orcamento->id,
                cadastroId: $cliente->id,
            )->onQueue('high');
        }

        $canal = match ($via) {
            'both' => 'WhatsApp e e-mail',
            'email' => 'e-mail',
            default => 'WhatsApp',
        };

        Log::info("[SendOrcamentoPdfAction] Orçamento #{$orcamento->numero} enfileirado para envio via {$canal}");

        return [
            'queued' => true,
            'message' => "Orçamento #{$orcamento->numero} enviado para a fila de disparo via {$canal}!",
            'whatsapp_link' => $whatsappLink,
        ];
    }
}
