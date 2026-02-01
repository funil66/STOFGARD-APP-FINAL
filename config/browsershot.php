<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Browsershot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Spatie Browsershot package used for PDF generation.
    | These paths should point to the correct Chrome/Node installations.
    |
    */

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/google-chrome-stable'),
    'node_path' => env('BROWSERSHOT_NODE_PATH', '/usr/bin/node'),
    'npm_path' => env('BROWSERSHOT_NPM_PATH', '/usr/bin/npm'),

    /*
    |--------------------------------------------------------------------------
    | Chrome Arguments
    |--------------------------------------------------------------------------
    |
    | Additional arguments passed to Chrome/Chromium for PDF generation.
    | These are required for proper operation in containerized environments.
    |
    */

    'chrome_args' => [
        '--disable-web-security',
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
    | Timeout in seconds for PDF generation.
    |
    */

    'timeout' => env('BROWSERSHOT_TIMEOUT', 60),

];