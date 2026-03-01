<?php

namespace App\Services;

use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AsaasTenantService — Integração Asaas no contexto do tenant.
 * O autônomo usa este serviço para cobrar seus clientes finais via PIX/Boleto.
 *
 * Diferente do AsaasService (Super Admin), este usa o token do TENANT.
 */
class AsaasTenantService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = config('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
    }

    /**
     * Gera cobrança PIX para um Orçamento.
     */
    public function gerarPix($orcamento): array
    {
        // Garante ou cria o cliente no Asaas do tenant
        $customerId = $this->garantirCliente($orcamento->cliente);

        $vencimento = now()->addHours(24)->format('Y-m-d');

        $response = $this->request('POST', '/payments', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => (float) $orcamento->valor_total,
            'dueDate' => $vencimento,
            'description' => "Orçamento #{$orcamento->numero} - " . ($orcamento->cliente->nome ?? ''),
            'externalReference' => "orcamento_{$orcamento->id}",
        ]);

        $cobrancaId = $response['id'];

        // Busca o QR Code PIX
        $pixData = $this->request('GET', "/payments/{$cobrancaId}/pixQrCode");

        Log::info('[AsaasTenantService] PIX gerado', ['orcamento_id' => $orcamento->id, 'cobranca_id' => $cobrancaId]);

        return [
            'pix_copia_cola' => $pixData['payload'] ?? null,
            'qr_code_base64' => $pixData['encodedImage'] ?? null,
            'link_pagamento' => $response['invoiceUrl'] ?? null,
            'cobranca_id' => $cobrancaId,
            'expires_at' => $vencimento,
            'fallback_manual' => false,
        ];
    }

    /**
     * Gera cobrança Boleto para um Orçamento.
     */
    public function gerarBoleto($orcamento): array
    {
        $customerId = $this->garantirCliente($orcamento->cliente);

        $response = $this->request('POST', '/payments', [
            'customer' => $customerId,
            'billingType' => 'BOLETO',
            'value' => (float) $orcamento->valor_total,
            'dueDate' => now()->addDays(3)->format('Y-m-d'),
            'description' => "Orçamento #{$orcamento->numero} - " . ($orcamento->cliente->nome ?? ''),
            'externalReference' => "orcamento_{$orcamento->id}",
        ]);

        return [
            'boleto_url' => $response['bankSlipUrl'] ?? null,
            'codigo_barras' => $response['nossoNumero'] ?? null,
            'link_pagamento' => $response['invoiceUrl'] ?? null,
            'cobranca_id' => $response['id'],
            'expires_at' => now()->addDays(3)->format('Y-m-d'),
        ];
    }

    /**
     * Consulta o status de uma cobrança.
     */
    public function consultarPagamento(string $cobrancaId): array
    {
        $response = $this->request('GET', "/payments/{$cobrancaId}");

        return [
            'status' => $response['status'] ?? 'UNKNOWN',
            'valor' => $response['value'] ?? 0,
            'data_pago' => $response['paymentDate'] ?? null,
            'net_value' => $response['netValue'] ?? null,
        ];
    }

    /**
     * Cancela/estorna uma cobrança.
     */
    public function cancelarCobranca(string $cobrancaId): bool
    {
        try {
            $this->request('DELETE', "/payments/{$cobrancaId}");
            return true;
        } catch (\Exception $e) {
            Log::error('[AsaasTenantService] Falha ao cancelar cobrança', ['id' => $cobrancaId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Garante que o cliente existe no Asaas do tenant. Cria se não existir.
     */
    private function garantirCliente($cliente): string
    {
        // Busca por referência externa
        $result = $this->request('GET', '/customers', [
            'externalReference' => "cliente_{$cliente->id}",
        ]);

        if (!empty($result['data'])) {
            return $result['data'][0]['id'];
        }

        // Cria o cliente
        $response = $this->request('POST', '/customers', [
            'name' => $cliente->nome,
            'email' => $cliente->email ?? null,
            'cpfCnpj' => $cliente->cpf ?? $cliente->cnpj ?? null,
            'phone' => $cliente->telefone ?? null,
            'mobilePhone' => $cliente->celular ?? null,
            'externalReference' => "cliente_{$cliente->id}",
        ]);

        return $response['id'];
    }

    /**
     * Executa requisição autenticada na API Asaas (contexto tenant).
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;

        $http = Http::withHeaders([
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(15);

        $result = match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url),
        };

        if ($result->failed()) {
            throw new \RuntimeException(
                "Asaas Tenant API Error [{$result->status()}]: " . ($result->json('errors.0.description') ?? $result->body())
            );
        }

        return $result->json() ?? [];
    }
}
