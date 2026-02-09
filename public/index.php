<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Capture request so we can add lightweight entry/exit logs for debugging 419s on local env
$request = Request::capture();
if (str_contains($request->path(), 'debug/upload-test')) {
    try {
        \Illuminate\Support\Facades\Log::info('Entry-point: incoming request', [
            'path' => $request->path(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'cookies' => $request->cookies->all(),
            'content_length' => $request->server('CONTENT_LENGTH'),
            'content_type' => $request->server('CONTENT_TYPE'),
            'raw_files' => isset($_FILES) ? $_FILES : null,
        ]);
    } catch (\Throwable $e) {
        // Best-effort logging only
        error_log('Failed to log entry-point request: '.$e->getMessage());
    }
}

try {
    $app->handleRequest($request);
} catch (\Throwable $e) {
    if (str_contains($request->path(), 'debug/upload-test')) {
        try {
            \Illuminate\Support\Facades\Log::error('Entry-point: exception during handleRequest', [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'path' => $request->path(),
                'headers' => $request->headers->all(),
            ]);
        } catch (\Throwable $logErr) {
            error_log('Failed to log handleRequest exception: '.$logErr->getMessage());
        }
    }

    throw $e;
}
