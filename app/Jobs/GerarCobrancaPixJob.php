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
                $mensagem = "Fala {$this->nomeCliente}! Tudo certo? 🚀\n\n";
                $mensagem .= "Segue o PIX para pagamento referente a {$this->documentoReferencia}:\n\n";
                $mensagem .= "Valor: R$ " . number_format($this->valorCobranca, 2, ',', '.') . "\n";
                $mensagem .= "Link para QR Code: {$dadosPix['link_visualizacao']}\n\n";
                $mensagem .= "PIX Copia e Cola 👇\n";
                $mensagem .= $dadosPix['pix_copia_cola'];

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
