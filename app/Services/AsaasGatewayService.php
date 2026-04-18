<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AsaasGatewayService — Única fonte de verdade para a API Asaas.
 * Resolve dinamicamente o contexto: Landlord (SuperAdmin) ou Tenant.
 */
class AsaasGatewayService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        $this->resolveApiKey();
    }

    private function resolveApiKey(): void
    {
        if (tenancy()->initialized()) {
            // Contexto Tenant: Lê as configurações financeiras do cliente logado
            $this->apiKey = \App\Models\Configuracao::first()->asaas_api_key ?? '';
        } else {
            // Contexto Landlord: Lê o .env principal
            $this->apiKey = env('ASAAS_API_KEY', '');
        }
    }

    public function gerarPix(object $dados, string $documento, string $nome, string $referenciaExterna): array
    {
        $customerId = $this->garantirCliente(['name' => $nome, 'externalReference' => "cliente_{$nome}"]);

        $response = $this->request('POST', '/payments', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => (float) $dados->valor_total,
            'dueDate' => now()->addDays(1)->format('Y-m-d'),
            'description' => "Cobrança ref: {$documento}",
            'externalReference' => $referenciaExterna,
        ]);

        $pixData = $this->request('GET', "/payments/{$response['id']}/pixQrCode");

        return [
            'pix_copia_cola' => $pixData['payload'] ?? null,
            'qr_code_base64' => $pixData['encodedImage'] ?? null,
            'link_pagamento' => $response['invoiceUrl'] ?? null,
            'cobranca_id' => $response['id'],
            'fallback_manual' => false,
        ];
    }

    public function criarAssinatura(string $customerId, float $valor, string $plano): array
    {
        return $this->request('POST', '/subscriptions', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => $valor,
            'nextDueDate' => now()->addDays(1)->format('Y-m-d'),
            'cycle' => 'MONTHLY',
            'description' => "Assinatura Plano {$plano}",
        ]);
    }

    public function garantirCliente(array $dados): string
    {
        $ref = $dados['externalReference'] ?? null;
        if ($ref) {
            $busca = $this->request('GET', '/customers', ['externalReference' => $ref]);
            if (!empty($busca['data'])) return $busca['data'][0]['id'];
        }

        $res = $this->request('POST', '/customers', [
            'name' => $dados['name'],
            'cpfCnpj' => $dados['cpfCnpj'] ?? null,
            'externalReference' => $ref,
        ]);

        return $res['id'];
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Asaas API Key não configurada para o contexto atual.');
        }

        $url = rtrim($this->baseUrl, '/') . $endpoint;
        $httpMethod = strtoupper($method);

        if ($httpMethod === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $curl = curl_init();
        $payload = in_array($httpMethod, ['POST', 'PUT']) ? json_encode($data) : null;

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => $httpMethod,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                "access_token: {$this->apiKey}",
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $json = json_decode($body, true);

        if ($status >= 400) {
            Log::error('[AsaasGateway] API Error', ['status' => $status, 'response' => $body]);
            throw new \RuntimeException("Asaas Error: " . ($json['errors'][0]['description'] ?? 'Erro desconhecido'));
        }

        return $json ?? [];
    }
}
