<?php

namespace App\Services\LlmProviders;

class ClaudeHaikuLlmProvider implements LlmProviderInterface
{
    public function __construct()
    {
        // Provider removed — kept lightweight placeholder in case of future reintroduction.
    }

    public function generate(string $prompt, array $options = []): string
    {
        return 'claude_haiku_response: (provider removed)';
    }
}
