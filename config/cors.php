<?php

$baseDomain = env('APP_BASE_DOMAIN', 'autonomia.app.br');
$escapedBaseDomain = preg_quote($baseDomain, '/');

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        "https://app.{$baseDomain}",
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/app\.' . $escapedBaseDomain . '$/',
        '/^https:\/\/([a-z0-9-]+)\.' . $escapedBaseDomain . '$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
