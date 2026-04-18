<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->append(\App\Http\Middleware\DomainTopologyMiddleware::class);
        $middleware->redirectGuestsTo(function (Request $request): string {
            return url('/admin/login');
        });

        $middleware->alias([
            'tenant.jwt' => \App\Http\Middleware\AuthenticateTenantJwt::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'livewire/*',
            'super-admin/*',
            'webhook/asaas',
            'api/webhooks/asaas',
            'api/webhooks/pix',
            'webhook/pix'
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\InitializeTenancyForLivewire::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 👁️ O OLHO DE SAURON (IRON CODE): Monitoramento de Erros Nível Enterprise
        Integration::handles($exceptions);
    })->create();
