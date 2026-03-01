<?php

namespace App\Traits;

use Illuminate\Support\Facades\Request;

trait HasLegalSignature
{
    /**
     * Gera o registro imutável da assinatura eletrônica com validade jurídica.
     * Padrão Iron Code contra caloteiros.
     */
    public function registerLegalSignature(string $signatureBase64): void
    {
        // Monta a string que será transformada em Hash (ID + Valor + Data + O Desenho)
        // Se um único pixel do desenho ou centavo do valor mudar, o Hash muda. É prova irrefutável.
        $valorBase = $this->valor_total ?? 0;
        $dataToHash = "{$this->id}|{$valorBase}|" . now()->toIso8601String() . "|{$signatureBase64}";

        $this->updateQuietly([
            'assinatura_ip' => Request::ip(),
            'assinatura_user_agent' => Request::userAgent(),
            'assinatura_timestamp' => now(),
            'assinatura_hash' => hash('sha256', $dataToHash),
        ]);
    }
}
