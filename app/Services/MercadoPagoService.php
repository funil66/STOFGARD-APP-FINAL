<?php

namespace App\Services;

/**
 * MercadoPagoService — Stub para integração Mercado Pago.
 * TODO: Implementar quando solicitado. Segue a mesma interface que AsaasTenantService.
 */
class MercadoPagoService
{
    public function __construct(string $apiKey)
    {
        // TODO: Inicializar SDK do Mercado Pago
    }

    public function gerarPix($orcamento): array
    {
        throw new \RuntimeException('MercadoPagoService não implementado ainda. Use Asaas por enquanto.');
    }

    public function gerarBoleto($orcamento): array
    {
        throw new \RuntimeException('MercadoPagoService não implementado ainda.');
    }

    public function consultarPagamento(string $cobrancaId): array
    {
        throw new \RuntimeException('MercadoPagoService não implementado ainda.');
    }

    public function cancelarCobranca(string $cobrancaId): bool
    {
        throw new \RuntimeException('MercadoPagoService não implementado ainda.');
    }
}
