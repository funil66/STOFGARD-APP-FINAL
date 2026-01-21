<?php

namespace App\Services\LlmProviders;

interface LlmProviderInterface
{
    /**
     * Generate a text response for given prompt.
     * Implementation should be synchronous and deterministic for tests.
     *
     * @param string $prompt
     * @param array $options
     * @return string
     */
    public function generate(string $prompt, array $options = []): string;
}
