<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/google/callback'),
    ],

    'browsershot' => [
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/home/sail/.cache/puppeteer/chrome/linux-144.0.7559.96/chrome-linux64/chrome'),
        'node_path' => env('BROWSERSHOT_NODE_PATH', '/usr/bin/node'),
        'npm_path' => env('BROWSERSHOT_NPM_PATH', '/usr/bin/npm'),
        'timeout' => env('BROWSERSHOT_TIMEOUT', 60),
    ],

    'openweather' => [
        'api_key' => env('OPENWEATHER_API_KEY'),
        'default_city' => env('OPENWEATHER_DEFAULT_CITY', 'São Paulo'),
    ],

];
