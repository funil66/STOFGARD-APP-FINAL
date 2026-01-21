<?php

namespace App\Http\Controllers;

use App\Services\PixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PixWebhookController extends Controller
{
    /**
     * Processa notificação de pagamento PIX da EFI
     */
    public function handle(Request $request)
    {
        Log::info('Webhook PIX recebido', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $pixService = new PixService;
            $result = $pixService->processarWebhook($request->all());

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook processado com sucesso',
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Falha ao processar webhook',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook PIX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint para verificar status do webhook (útil para testes)
     */
    public function status()
    {
        return response()->json([
            'status' => 'online',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
