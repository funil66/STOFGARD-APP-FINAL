<?php
// ⚠️ THIS FILE IS DEPRECATED AND SCHEDULED FOR DELETION
// The legacy PixService has been superseded by:
//   - App\Services\Pix\PixMasterService (QR Code generation)
//   - App\Services\Pix\PixKeyValidatorService (Key validation)
//   - App\Services\GatewayService (Multi-gateway facade)
//
// DELETE THIS FILE: rm app/Services/PixService.php

namespace App\Services;

/**
 * @deprecated Use PixMasterService or GatewayService instead
 */
class PixService
{
    public function __call($method, $args)
    {
        throw new \RuntimeException(
            "PixService is deprecated. Use App\\Services\\Pix\\PixMasterService or App\\Services\\GatewayService instead. Method called: {$method}"
        );
    }
}
