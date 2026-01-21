<?php

namespace App\Services\LlmProviders\Adapters;

// Interface removed — kept as a placeholder for future adapters.
interface ClaudeApiAdapterInterface
{
    public function sendPrompt(string $prompt, array $options = []): string;
}
