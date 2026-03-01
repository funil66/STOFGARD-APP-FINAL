<?php

namespace App\Traits;

use Illuminate\Support\Facades\Request;

trait HasLegalSignature
{
    /**
     * Gera o registo imutável da assinatura eletrónica com validade jurídica.
     * Padrão Iron Code contra caloteiros.
     */
    public function registerLegalSignature(string $signatureBase64): void
    {
        // Monta a string que será transformada em Hash.
        // Se 1 pixel do desenho ou 1 centavo mudar, o Hash rebenta. É prova irrefutável.
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
