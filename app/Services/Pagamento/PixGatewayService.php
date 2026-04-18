<?php
// ⚠️ THIS FILE IS DEPRECATED AND SCHEDULED FOR DELETION
// The simulated PixGatewayService has been superseded by GatewayService
// which properly resolves the real gateway per tenant.
//
// DELETE THIS FILE: rm app/Services/Pagamento/PixGatewayService.php

namespace App\Services\Pagamento;

/**
 * @deprecated Use App\Services\GatewayService instead
 */
class PixGatewayService
{
    public function __call($method, $args)
    {
        throw new \RuntimeException(
            "PixGatewayService is deprecated. Use App\\Services\\GatewayService instead. Method called: {$method}"
        );
    }
}
