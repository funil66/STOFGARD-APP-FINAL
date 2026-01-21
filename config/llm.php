<?php

return [
    // Third-party LLM model configuration and feature flags.
    'models' => [
        // No external models configured by default. Add entries here for future providers.
    ],

    // Default feature key used on Cliente.features
    'feature_keys' => [
        // No feature keys by default
    ],

    // Provider mapping: model key => provider class
    'providers' => [
        'default' => \App\Services\LlmProviders\DefaultLlmProvider::class,
    ],

    // Adapter configuration for providers that require it
    'adapters' => [
        // Add provider-specific adapters here if needed in the future
    ],
];