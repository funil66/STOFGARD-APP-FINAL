<?php

namespace App\Services;

/**
 * EfiPayService — Stub para integração EFI/Gerencianet.
 * TODO: Implementar quando solicitado. Segue a mesma interface que AsaasGatewayService.
 */
class EfiPayService
{
    public function __construct(string $apiKey)
    {
        // TODO: Inicializar SDK ou HTTP client da EFI
    }

    public function gerarPix($orcamento): array
    {
        throw new \RuntimeException('EfiPayService não implementado ainda. Use Asaas por enquanto.');
    }

    public function gerarBoleto($orcamento): array
    {
        throw new \RuntimeException('EfiPayService não implementado ainda.');
    }

    public function consultarPagamento(string $cobrancaId): array
    {
        throw new \RuntimeException('EfiPayService não implementado ainda.');
    }

    public function cancelarCobranca(string $cobrancaId): bool
    {
        throw new \RuntimeException('EfiPayService não implementado ainda.');
    }
}
