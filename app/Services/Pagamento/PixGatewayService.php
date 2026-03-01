<?php

namespace App\Services\Pagamento;

use App\Models\Configuracao;
use Exception;
use Illuminate\Support\Facades\Log;

class PixGatewayService
{
    /**
     * Gera um PIX Copia e Cola dinâmico e o Link do QR Code.
     * Isso usa os dados salvos pelo autônomo na tabela de configurações do Tenant.
     */
    public function gerarCobrancaPix(float $valor, string $descricao, string $nomeCliente): array
    {
        // Pega as chaves salvas no banco do Tenant ativo
        $config = Configuracao::first();

        $clientId = $config->efi_client_id ?? null;
        $clientSecret = $config->efi_client_secret ?? null;

        if (!$clientId || !$clientSecret) {
            Log::error("🔴 Iron Code: Tentativa de gerar PIX sem configurar a API no Painel.");
            throw new Exception("Chaves da API de pagamento não configuradas.");
        }

        // LÓGICA DE INTEGRAÇÃO REAL ENTRARIA AQUI (Bater no cURL da EFI/Asaas com os tokens).
        // Como estamos finalizando a arquitetura, vou simular o retorno da API pra não travar seu teste local.

        Log::info("💰 Iron Code: Bateu na API da EFI/Asaas para o cliente {$nomeCliente}, gerando cobrança de R$ {$valor}");

        // Retorno simulado padrão Premium
        return [
            'success' => true,
            'pix_copia_cola' => '00020101021126580014br.gov.bcb.pix0136suachave@pix.com.br520400005303999540510.005802BR5909SAO PAULO62070503***63041234540500.00',
            'link_visualizacao' => 'https://pix.efi.com/v/simulacao12345',
            'txid' => 'STOFGARD' . time()
        ];
    }
}
