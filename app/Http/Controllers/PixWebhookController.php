<?php
// ⚠️ THIS FILE IS DEPRECATED AND SCHEDULED FOR DELETION
// Legacy PIX webhook - the real controller is at:
//   App\Http\Controllers\Webhooks\PixWebhookController
//   Route: POST api/webhooks/pix/{webhookToken}
//
// DELETE THIS FILE: rm app/Http/Controllers/PixWebhookController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @deprecated Use App\Http\Controllers\Webhooks\PixWebhookController instead
 */
class PixWebhookController extends Controller
{
    public function handle(Request $request)
    {
        return response()->json([
            'status' => 'deprecated',
            'message' => 'Este endpoint foi descontinuado. Use POST /api/webhooks/pix/{webhookToken}',
        ], 410);
    }

    public function status()
    {
        return response()->json([
            'status' => 'deprecated',
            'message' => 'Este endpoint foi descontinuado.',
        ], 410);
    }
}
