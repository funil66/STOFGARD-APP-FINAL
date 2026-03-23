<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::prefix('cliente')->name('cliente.')->group(function () {
        Route::get('/acesso/{token}', [\App\Http\Controllers\MagicLinkController::class, 'consumir'])
            ->name('magic-link.consumir');
        Route::get('/link-invalido', [\App\Http\Controllers\MagicLinkController::class, 'invalido'])
            ->name('magic-link.invalido');
        Route::post('/logout', [\App\Http\Controllers\MagicLinkController::class, 'logout'])
            ->name('logout');

        Route::middleware([\App\Http\Middleware\ClienteAutenticado::class])->group(function () {
            Route::get('/', [\App\Http\Controllers\PortalClienteController::class, 'index'])
                ->name('portal');
            Route::get('/orcamento/{id}', [\App\Http\Controllers\PortalClienteController::class, 'orcamento'])
                ->name('orcamento');
            Route::get('/os/{id}', [\App\Http\Controllers\PortalClienteController::class, 'ordemServico'])
                ->name('os');
            Route::get('/nota-fiscal/{id}', [\App\Http\Controllers\PortalClienteController::class, 'notaFiscal'])
                ->name('nota-fiscal');
            Route::get('/orcamento/{orcamento}/aprovar/{opcao}', [\App\Http\Controllers\PortalClienteController::class, 'aprovarOpcao'])
                ->name('aprovar_opcao')
                ->where('opcao', '[ABC]');
        });
    });
});
