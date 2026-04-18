<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AsaasGatewayService — Única fonte de verdade para a API Asaas.
 * Suporta operações de Landlord (Super Admin) e Tenant via injeção dinâmica de token.
 */
class AsaasGatewayService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->baseUrl = config('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        
        if ($apiKey) {
            $this->apiKey = trim($apiKey);
        } else {
            $this->apiKey = trim((string) config('services.asaas.api_key', env('ASAAS_API_KEY', '')));
        }
    }

    // =========================================================================
    // CLIENTES
    // =========================================================================

    public function criarCliente(array $dados): array
    {
        $response = $this->request('POST', '/customers', [
            'name' => $dados['name'],
            'email' => $dados['email'] ?? null,
            'cpfCnpj' => $dados['cpf_cnpj'] ?? $dados['cpfCnpj'] ?? null,
            'phone' => $dados['phone'] ?? null,
            'mobilePhone' => $dados['mobilePhone'] ?? null,
            'externalReference' => $dados['tenant_id'] ?? $dados['externalReference'] ?? null,
        ]);

        Log::info('[AsaasGatewayService] Cliente criado', ['asaas_id' => $response['id'] ?? null]);

        return $response;
    }

    public function buscarCliente(string $customerId): array
    {
        return $this->request('GET', "/customers/{$customerId}");
    }

    public function buscarClientePorReferencia(string $referencia): ?array
    {
        $result = $this->request('GET', '/customers', [
            'externalReference' => $referencia,
        ]);

        return $result['data'][0] ?? null;
    }

    public function garantirCliente($cliente): string
    {
        // Aceita array ou objeto
        $id = is_array($cliente) ? ($cliente['id'] ?? null) : ($cliente->id ?? null);
        $nome = is_array($cliente) ? ($cliente['name'] ?? $cliente['nome'] ?? null) : ($cliente->nome ?? $cliente->name ?? null);
        $email = is_array($cliente) ? ($cliente['email'] ?? null) : ($cliente->email ?? null);
        $cpfCnpj = is_array($cliente) ? ($cliente['cpfCnpj'] ?? $cliente['cpf_cnpj'] ?? null) : ($cliente->cpf ?? $cliente->cnpj ?? null);
        $telefone = is_array($cliente) ? ($cliente['phone'] ?? $cliente['telefone'] ?? null) : ($cliente->telefone ?? null);
        
        $ref = is_array($cliente) ? ($cliente['externalReference'] ?? "cliente_{$id}") : "cliente_{$id}";

        $busca = $this->buscarClientePorReferencia($ref);
        if ($busca) {
            return $busca['id'];
        }

        $res = $this->criarCliente([
            'name' => $nome,
            'email' => $email,
            'cpfCnpj' => $cpfCnpj,
            'phone' => $telefone,
            'externalReference' => $ref,
        ]);

        return $res['id'];
    }

    // =========================================================================
    // ASSINATURAS E COBRANÇAS
    // =========================================================================

    public function createSubscription(array $dadosCliente, float $valor, string $ciclo = 'MONTHLY'): array
    {
        $tenantId = $dadosCliente['tenant_id'] ?? ($dadosCliente['id'] ?? null);
        $clienteAsaas = $tenantId ? $this->buscarClientePorReferencia((string) $tenantId) : null;

        if (!$clienteAsaas) {
            $clienteAsaas = $this->criarCliente($dadosCliente);
        }

        $billingType = strtoupper($dadosCliente['billingType'] ?? 'PIX');
        if (!in_array($billingType, ['CREDIT_CARD', 'PIX', 'BOLETO'], true)) $billingType = 'PIX';

        return $this->request('POST', '/subscriptions', [
            'customer' => $clienteAsaas['id'],
            'billingType' => $billingType,
            'value' => $valor,
            'nextDueDate' => now()->addDays(1)->format('Y-m-d'),
            'cycle' => strtoupper($ciclo),
            'description' => $dadosCliente['descricao'] ?? "Assinatura Autonomia - {$ciclo}",
            'externalReference' => $tenantId,
        ]);
    }

    public function criarAssinatura(string $customerId, float $valor, string $plano, string $billingType = 'CREDIT_CARD'): array
    {
        $billingType = strtoupper($billingType);
        if (!in_array($billingType, ['CREDIT_CARD', 'PIX', 'BOLETO'], true)) $billingType = 'CREDIT_CARD';

        return $this->request('POST', '/subscriptions', [
            'customer' => $customerId,
            'billingType' => $billingType,
            'value' => $valor,
            'nextDueDate' => now()->addDays(1)->format('Y-m-d'),
            'cycle' => 'MONTHLY',
            'description' => "Assinatura Plano {$plano}",
            'externalReference' => $plano,
        ]);
    }

    public function consultarAssinatura(string $subscriptionId): array
    {
        return $this->request('GET', "/subscriptions/{$subscriptionId}");
    }

    public function cancelarAssinatura(string $subscriptionId): array
    {
        return $this->request('DELETE', "/subscriptions/{$subscriptionId}");
    }

    public function criarCobranca(string $customerId, float $valor, string $descricao, string $vencimento): array
    {
        return $this->request('POST', '/payments', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => $valor,
            'dueDate' => $vencimento,
            'description' => $descricao,
        ]);
    }

    public function obterQrCodePix(string $paymentId): array
    {
        return $this->request('GET', "/payments/{$paymentId}/pixQrCode");
    }

    // =========================================================================
    // MÉTODOS DE TENANT (PIX E BOLETO PARA CLIENTE FINAL)
    // =========================================================================

    public function gerarPix($orcamento): array
    {
        $customerId = $this->garantirCliente($orcamento->cliente);
        $vencimento = now()->addHours(24)->format('Y-m-d');

        $response = $this->criarCobranca(
            $customerId,
            (float) $orcamento->valor_total,
            "Orçamento #{$orcamento->numero} - " . ($orcamento->cliente->nome ?? ''),
            $vencimento
        );

        $cobrancaId = $response['id'];
        $pixData = $this->obterQrCodePix($cobrancaId);

        return [
            'pix_copia_cola' => $pixData['payload'] ?? null,
            'qr_code_base64' => $pixData['encodedImage'] ?? null,
            'link_pagamento' => $response['invoiceUrl'] ?? null,
            'cobranca_id' => $cobrancaId,
            'expires_at' => $vencimento,
            'fallback_manual' => false,
        ];
    }

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

    public function cancelarCobranca(string $cobrancaId): bool
    {
        try {
            $this->request('DELETE', "/payments/{$cobrancaId}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // UTILITÁRIOS
    // =========================================================================

    private function request(string $method, string $endpoint, array $data = []): array
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('ASAAS_API_KEY não configurada.');
        }

        $url = rtrim($this->baseUrl, '/') . $endpoint;
        $httpMethod = strtoupper($method);

        if ($httpMethod === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $curl = curl_init();
        $payload = in_array($httpMethod, ['POST', 'PUT']) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;

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
            Log::error('[AsaasGatewayService] Requisição falhou', ['endpoint' => $endpoint, 'status' => $status, 'body' => $body]);
            throw new \RuntimeException("Asaas Error: " . ($json['errors'][0]['description'] ?? 'Erro desconhecido'));
        }

        return $json ?? [];
    }
}
