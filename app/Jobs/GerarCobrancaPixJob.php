<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GatewayService;
use App\Jobs\SendWhatsAppJob;
use Illuminate\Support\Facades\Log;

class GerarCobrancaPixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $telefoneCliente;
    protected $nomeCliente;
    protected $valorCobranca;
    protected $documentoReferencia; // Ex: 'OS #0012'

    public function __construct($telefoneCliente, $nomeCliente, $valorCobranca, $documentoReferencia)
    {
        $this->telefoneCliente = $telefoneCliente;
        $this->nomeCliente = $nomeCliente;
        $this->valorCobranca = $valorCobranca;
        $this->documentoReferencia = $documentoReferencia;
    }

    public function handle()
    {
        try {
            if (!GatewayService::estaConfigurado()) {
                Log::warning('[GerarCobrancaPix] Gateway não configurado para este tenant');
                return;
            }

            // Monta dados para o gateway
            $dadosPix = GatewayService::gerarPix((object) [
                'valor_total' => $this->valorCobranca,
                'numero' => $this->documentoReferencia,
            ]);

            // GatewayService retorna: pix_copia_cola, qr_code_base64, link_pagamento, cobranca_id
            if (!empty($dadosPix['pix_copia_cola']) && empty($dadosPix['fallback_manual'])) {
                // Template configurável — lê de Settings, fallback para texto padrão
                $template = settings('texto_cobranca_pix');

                if (empty($template)) {
                    $template = "Fala {nome}! Tudo certo? 🚀\n\n"
                        . "Segue o PIX para pagamento referente a {documento}:\n\n"
                        . "Valor: R$ {valor}\n"
                        . "Link para QR Code: {link}\n\n"
                        . "PIX Copia e Cola 👇\n"
                        . "{pix_copia_cola}";
                }

                $mensagem = str_replace(
                    ['{nome}', '{documento}', '{valor}', '{link}', '{pix_copia_cola}'],
                    [
                        $this->nomeCliente,
                        $this->documentoReferencia,
                        number_format($this->valorCobranca, 2, ',', '.'),
                        $dadosPix['link_pagamento'] ?? '',
                        $dadosPix['pix_copia_cola'],
                    ],
                    $template
                );

                SendWhatsAppJob::dispatch($this->telefoneCliente, $mensagem);

                Log::info("[GerarCobrancaPix] PIX gerado e WhatsApp despachado para {$this->nomeCliente}");
            } else {
                Log::warning("[GerarCobrancaPix] Gateway retornou fallback manual — WhatsApp não enviado");
            }
        } catch (\Exception $e) {
            Log::error("💣 A C4 Falhou na cobrança: " . $e->getMessage());
            // Falha silenciosa pro usuário, mas logada pro Sentry te avisar.
            throw $e;
        }
    }
}
