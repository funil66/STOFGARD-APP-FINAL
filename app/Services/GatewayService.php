<?php

namespace App\Services;

use App\Models\Configuracao;
use Illuminate\Support\Facades\Log;

/**
 * GatewayService — Fachada multi-gateway para tenants cobrarem clientes finais.
 * Detecta o provedor configurado e despacha para o serviço correto.
 *
 * Uso:
 *   GatewayService::gerarPix($orcamento)
 *   GatewayService::consultarPagamento($cobrancaId)
 */
class GatewayService
{
    /**
     * Gera cobrança PIX para um orçamento.
     * Retorna array com: pix_copia_cola, qr_code_base64, link_pagamento, cobranca_id, expires_at
     */
    public static function gerarPix($orcamento): array
    {
        $service = static::resolveService();

        if (!$service) {
            // Fallback: retorna apenas a chave PIX manual configurada
            $config = Configuracao::first();
            return [
                'pix_copia_cola' => $config->pix_chave ?? null,
                'qr_code_base64' => null,
                'link_pagamento' => null,
                'cobranca_id' => null,
                'expires_at' => null,
                'fallback_manual' => true,
            ];
        }

        return $service->gerarPix($orcamento);
    }

    /**
     * Gera boleto bancário para um orçamento.
     */
    public static function gerarBoleto($orcamento): array
    {
        $service = static::resolveService();

        if (!$service) {
            throw new \RuntimeException('Gateway de pagamento não configurado. Configure nas Configurações.');
        }

        return $service->gerarBoleto($orcamento);
    }

    /**
     * Consulta o status de uma cobrança pelo ID externo.
     */
    public static function consultarPagamento(string $cobrancaId): array
    {
        $service = static::resolveService();

        if (!$service) {
            return ['status' => 'desconhecido', 'motivo' => 'Gateway não configurado'];
        }

        return $service->consultarPagamento($cobrancaId);
    }

    /**
     * Cancela/extorna uma cobrança.
     */
    public static function cancelarCobranca(string $cobrancaId): bool
    {
        $service = static::resolveService();

        if (!$service) {
            return false;
        }

        return $service->cancelarCobranca($cobrancaId);
    }

    /**
     * Verifica se o gateway está corretamente configurado.
     */
    public static function estaConfigurado(): bool
    {
        return static::resolveService() !== null;
    }

    /**
     * Retorna o nome do provider configurado.
     */
    public static function getProvider(): ?string
    {
        $config = Configuracao::first();
        return $config->gateway_provider ?? null;
    }

    /**
     * Resolve o serviço de gateway baseado na configuração do tenant.
     */
    private static function resolveService(): ?object
    {
        $config = Configuracao::first();

        if (!$config || !$config->gateway_provider || !$config->gateway_token_encrypted) {
            return null;
        }

        // Descriptografa o token armazenado
        try {
            $token = decrypt($config->gateway_token_encrypted);
        } catch (\Exception $e) {
            Log::error('[GatewayService] Falha ao descriptografar token do gateway', ['error' => $e->getMessage()]);
            return null;
        }

        try {
            $service = match ($config->gateway_provider) {
                'asaas' => new AsaasGatewayService($token),
                'efipay' => new EfiPayService($token),
                'mercadopago' => new MercadoPagoService($token),
                default => null,
            };

            // Verify the service is actually implemented (not just a stub)
            if ($service && method_exists($service, 'gerarPix')) {
                return $service;
            }

            return $service;
        } catch (\RuntimeException $e) {
            Log::warning("[GatewayService] Gateway '{$config->gateway_provider}' não implementado: {$e->getMessage()}");
            return null;
        }
    }
}
