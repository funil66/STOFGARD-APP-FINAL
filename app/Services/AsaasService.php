<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AsaasService — Integração com Asaas para o Super Admin.
 * Gerencia clientes (tenants) e assinaturas SaaS.
 *
 * Documentação: https://docs.asaas.com/reference
 */
class AsaasService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        $this->apiKey = config('services.asaas.api_key', '');
    }

    // =========================================================================
    // CLIENTES
    // =========================================================================

    /**
     * Cria um cliente no Asaas para o tenant.
     */
    public function criarCliente(array $dados): array
    {
        $response = $this->request('POST', '/customers', [
            'name' => $dados['name'],
            'email' => $dados['email'] ?? null,
            'cpfCnpj' => $dados['cpf_cnpj'] ?? null,
            'phone' => $dados['phone'] ?? null,
            'externalReference' => $dados['tenant_id'] ?? null,
        ]);

        Log::info('[AsaasService] Cliente criado', ['tenant' => $dados['tenant_id'], 'asaas_id' => $response['id'] ?? null]);

        return $response;
    }

    /**
     * Busca cliente pelo ID Asaas.
     */
    public function buscarCliente(string $customerId): array
    {
        return $this->request('GET', "/customers/{$customerId}");
    }

    /**
     * Busca cliente por referência externa (tenant_id).
     */
    public function buscarClientePorReferencia(string $tenantId): ?array
    {
        $result = $this->request('GET', '/customers', [
            'externalReference' => $tenantId,
        ]);

        return $result['data'][0] ?? null;
    }

    // =========================================================================
    // ASSINATURAS
    // =========================================================================

    /**
     * Cria uma assinatura recorrente mensal no Asaas.
     *
     * @param string $customerId ID do cliente no Asaas
     * @param float  $valor      Valor mensal da assinatura
     * @param string $plano      Nome do plano (PRO, Elite)
     */
    public function criarAssinatura(string $customerId, float $valor, string $plano): array
    {
        $response = $this->request('POST', '/subscriptions', [
            'customer' => $customerId,
            'billingType' => 'CREDIT_CARD', // ou 'BOLETO', 'PIX'
            'value' => $valor,
            'nextDueDate' => now()->addDays(1)->format('Y-m-d'), // 1º cobrança amanhã
            'cycle' => 'MONTHLY',
            'description' => "AUTONOMIA ILIMITADA - Plano {$plano}",
            'externalReference' => $plano,
        ]);

        Log::info('[AsaasService] Assinatura criada', ['customer' => $customerId, 'plano' => $plano]);

        return $response;
    }

    /**
     * Consulta o status da assinatura.
     */
    public function consultarAssinatura(string $subscriptionId): array
    {
        return $this->request('GET', "/subscriptions/{$subscriptionId}");
    }

    /**
     * Lista pagamentos de uma assinatura (para verificar pagamento recente).
     */
    public function listarPagamentosAssinatura(string $subscriptionId): array
    {
        return $this->request('GET', "/subscriptions/{$subscriptionId}/payments");
    }

    /**
     * Cancela a assinatura de um tenant.
     */
    public function cancelarAssinatura(string $subscriptionId): array
    {
        $response = $this->request('DELETE', "/subscriptions/{$subscriptionId}");
        Log::info('[AsaasService] Assinatura cancelada', ['subscription_id' => $subscriptionId]);
        return $response;
    }

    /**
     * Atualiza o valor ou status da assinatura (p.ex., upgrade de plano).
     */
    public function atualizarAssinatura(string $subscriptionId, array $dados): array
    {
        return $this->request('PUT', "/subscriptions/{$subscriptionId}", $dados);
    }

    // =========================================================================
    // COBRANÇAS AVULSAS (para cobranças pontuais)
    // =========================================================================

    /**
     * Cria uma cobrança avulsa (sem assinatura).
     */
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

    // =========================================================================
    // UTILITÁRIOS PRIVADOS
    // =========================================================================

    /**
     * Executa uma requisição autenticada na API do Asaas.
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;

        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(15);

        $result = match (strtoupper($method)) {
            'GET' => $response->get($url, $data),
            'POST' => $response->post($url, $data),
            'PUT' => $response->put($url, $data),
            'DELETE' => $response->delete($url),
            default => throw new \InvalidArgumentException("Método HTTP inválido: {$method}"),
        };

        if ($result->failed()) {
            Log::error('[AsaasService] Requisição falhou', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $result->status(),
                'body' => $result->body(),
            ]);

            throw new \RuntimeException(
                "Asaas API Error [{$result->status()}]: " . ($result->json('errors.0.description') ?? $result->body())
            );
        }

        return $result->json() ?? [];
    }
}
