<?php

namespace App\Services;

use App\Models\Financeiro;
use Illuminate\Support\Facades\Log;

class PixService
{
    public function criarCobranca(Financeiro $financeiro): bool
    {
        Log::warning('[PixService] Tentativa de uso do fluxo legado de cobrança PIX', [
            'financeiro_id' => $financeiro->id,
        ]);

        return false;
    }

    public function processarWebhook(array $payload): bool
    {
        Log::warning('[PixService] Tentativa de uso do webhook legado PIX', [
            'payload_keys' => array_keys($payload),
        ]);

        return false;
    }
}
