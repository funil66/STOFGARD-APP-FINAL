<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fluxos Legados de PIX
    |--------------------------------------------------------------------------
    | Mantidos apenas para rollback controlado. O trilho canônico é:
    | POST /api/webhooks/pix/{webhookToken}
    */
    'legacy_pix_flow_enabled' => env('LEGACY_PIX_FLOW_ENABLED', false),
    'legacy_pix_webhook_enabled' => env('LEGACY_PIX_WEBHOOK_ENABLED', false),
];
