<?php

namespace App\Services\LlmProviders;

class DefaultLlmProvider implements LlmProviderInterface
{
    public function generate(string $prompt, array $options = []): string
    {
        // Minimal deterministic stub for tests — in production this would call an external API
        return "default_provider_response: " . substr($prompt, 0, 100);
    }
}
