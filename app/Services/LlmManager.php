<?php

namespace App\Services;

use App\Models\Cliente;
use App\Services\LlmProviders\LlmProviderInterface;

class LlmManager
{
    protected LlmService $llmService;

    public function __construct(LlmService $llmService)
    {
        $this->llmService = $llmService;
    }

    /**
     * Generate text for a client using the appropriate provider based on feature flags.
     */
    public function generateForCliente(?Cliente $cliente, string $prompt, array $options = []): string
    {
        $modelKey = $this->llmService->chooseModelForCliente($cliente);

        // Resolve provider class from config
        $providers = config('llm.providers', []);
        $providerClass = $providers[$modelKey] ?? ($providers['default'] ?? null);

        if (! $providerClass) {
            throw new \RuntimeException('No LLM provider configured');
        }

        /** @var LlmProviderInterface $provider */
        $provider = app($providerClass);

        return $provider->generate($prompt, $options);
    }
}
