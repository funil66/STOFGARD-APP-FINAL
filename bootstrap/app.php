<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry: Captura todas as exceções não tratadas em produção.
        // Configurar SENTRY_DSN no .env de produção para ativar.
        $exceptions->report(function (\Throwable $e) {
            if (app()->bound('sentry') && config('sentry.dsn')) {
                \Sentry\captureException($e);
            }
        });
    })->create();
