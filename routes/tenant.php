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
    Route::get('/', function () {
        return redirect('/admin/login');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/os/{ordemServico}/garantia', [\App\Http\Controllers\GarantiaPdfController::class, 'gerarPorOrdemServico'])->name('os.garantia');
    });
    Route::get('/storage/{path}', function ($path) {
        $fullPath = storage_path("app/public/{$path}");
        if (!file_exists($fullPath)) {
            abort(404);
        }
        return response()->file($fullPath);
    })->where('path', '.*')->name('tenant.storage.serve');
});
