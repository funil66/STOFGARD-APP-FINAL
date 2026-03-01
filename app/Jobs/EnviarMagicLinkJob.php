<?php

namespace App\Jobs;

use App\Models\Cadastro;
use App\Models\ClienteAcesso;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * EnviarMagicLinkJob — Envia o link de acesso ao portal do cliente via WhatsApp.
 * Disparado quando uma OS é aprovada ou orçamento aprovado.
 */
class EnviarMagicLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $cadastroId,
        private readonly string $motivo = 'portal',
        private readonly ?int $resourceId = null
    ) {
    }

    public function handle(): void
    {
        $cliente = Cadastro::find($this->cadastroId);

        if (!$cliente || !$cliente->celular) {
            Log::info('[MagicLinkJob] Cliente sem celular, abort.', ['cadastro_id' => $this->cadastroId]);
            return;
        }

        // Cria token de acesso (válido por 48h)
        $acesso = ClienteAcesso::criarParaCliente(
            $this->cadastroId,
            $this->motivo,
            $this->resourceId,
            48
        );

        // Monta URL do magic link
        $url = route('magic-link.consumir', ['token' => $acesso->token]);

        // Monta mensagem personalizada por motivo
        $mensagem = match ($this->motivo) {
            'orcamento' => "📋 *Seu Orçamento está disponível!*\n\n"
            . "Olá, {$cliente->nome}! 👋\n\n"
            . "Acesse seu orçamento online (válido por 48h):\n"
            . "{$url}\n\n"
            . "_Não compartilhe este link._",

            'os' => "🔧 *Atualização do seu Serviço!*\n\n"
            . "Olá, {$cliente->nome}! 👋\n\n"
            . "Acompanhe o status do seu serviço:\n"
            . "{$url}\n\n"
            . "_Link válido por 48 horas._",

            default => "🔗 *Acesse seu Portal*\n\n"
            . "Olá, {$cliente->nome}! 👋\n\n"
            . "Clique para acessar seus serviços, orçamentos e notas fiscais:\n"
            . "{$url}\n\n"
            . "_Link válido por 48 horas. Não compartilhe._",
        };

        SendWhatsAppJob::dispatch($cliente->celular, $mensagem);

        Log::info('[MagicLinkJob] Magic link enviado', [
            'cadastro_id' => $this->cadastroId,
            'motivo' => $this->motivo,
            'expires_at' => $acesso->expires_at->toISOString(),
        ]);
    }
}
