<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Pagamento\PixGatewayService;
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
            $gateway = new PixGatewayService();
            $dadosPix = $gateway->gerarCobrancaPix($this->valorCobranca, $this->documentoReferencia, $this->nomeCliente);

            if ($dadosPix['success']) {
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
                        $dadosPix['link_visualizacao'],
                        $dadosPix['pix_copia_cola'],
                    ],
                    $template
                );

                // Despacha pro WhatsApp Job que já temos rodando nas trincheiras
                SendWhatsAppJob::dispatch($this->telefoneCliente, $mensagem);

                Log::info("✅ Fogo no buraco! PIX gerado e Zap despachado para {$this->nomeCliente}.");
            }
        } catch (\Exception $e) {
            Log::error("💣 A C4 Falhou na cobrança: " . $e->getMessage());
            // Falha silenciosa pro usuário, mas logada pro Sentry te avisar.
            throw $e;
        }
    }
}
