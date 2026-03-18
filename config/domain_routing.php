<?php

return [
    'base_domain' => env('APP_BASE_DOMAIN', 'autonomia.app.br'),
    'provider_subdomain' => env('APP_PROVIDER_SUBDOMAIN', 'app'),
    'provider_scheme' => env('APP_PROVIDER_SCHEME', 'https'),

    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'ttl_minutes' => (int) env('JWT_TTL_MINUTES', 480),
        'issuer' => env('JWT_ISSUER', env('APP_NAME', 'autonomia-app')),
    ],
];
