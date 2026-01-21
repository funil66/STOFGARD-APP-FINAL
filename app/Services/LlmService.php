<?php

namespace App\Services;

use App\Models\Cliente;

class LlmService
{
    // Returns a model key chosen for the provided cliente.
    // Currently only the default provider is configured.
    public function chooseModelForCliente(?Cliente $cliente = null): string
    {
        return 'default';
    }

    // Helper to check if a model is allowed for a cliente
    public function isModelAvailableForCliente(string $modelKey, ?Cliente $cliente = null): bool
    {
        if (! config("llm.models.{$modelKey}", false)) {
            return false;
        }

        $globallyEnabled = config("llm.models.{$modelKey}.enabled", false);

        if (! $globallyEnabled) {
            return false;
        }

        if ($cliente) {
            return $cliente->hasFeature($modelKey);
        }

        return $globallyEnabled;
    }
}
