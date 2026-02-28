<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modo de operação do Browsershot
    |--------------------------------------------------------------------------
    |
    | 'local'      → Chrome/Node instalados localmente (desenvolvimento sem Docker)
    | 'browserless' → Container Browserless via WebSocket (production-ready)
    |
    */

    'mode' => env('BROWSERSHOT_MODE', 'browserless'),

    /*
    |--------------------------------------------------------------------------
    | Configuração do Browserless (modo 'browserless')
    |--------------------------------------------------------------------------
    |
    | URL do container Browserless. No Docker Compose, use 'browserless' como host.
    | TOKEN deve ser definido em .env e ser igual a BROWSERLESS_TOKEN no compose.
    |
    */

    'browserless_url' => env('BROWSERLESS_URL', 'http://browserless:3000'),
    'browserless_token' => env('BROWSERLESS_TOKEN', 'localtoken'),

    /*
    |--------------------------------------------------------------------------
    | Configuração Local (modo 'local')
    |--------------------------------------------------------------------------
    |
    | Usado apenas em desenvolvimento sem Docker ou em testes.
    |
    */

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium'),
    'node_path' => env('BROWSERSHOT_NODE_PATH', '/usr/bin/node'),
    'npm_path' => env('BROWSERSHOT_NPM_PATH', '/usr/bin/npm'),

    /*
    |--------------------------------------------------------------------------
    | Argumentos do Chrome (modo 'local' apenas)
    |--------------------------------------------------------------------------
    */

    'chrome_args' => [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--headless',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout em segundos para geração de PDF.
    |
    */

    'timeout' => env('BROWSERSHOT_TIMEOUT', 60),

];
