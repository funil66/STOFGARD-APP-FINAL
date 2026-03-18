<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EvolutionWebhookController;
use App\Http\Controllers\Auth\TenantJwtLoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [TenantJwtLoginController::class, 'login'])
        ->name('api.auth.login');

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [TenantJwtLoginController::class, 'me'])
            ->name('api.auth.me');

        Route::post('/logout', [TenantJwtLoginController::class, 'logout'])
            ->name('api.auth.logout');
    });
});

// URL de Escuta: dominio.com/api/webhook/evolution
Route::middleware('throttle:120,1')->post('/webhook/evolution', [EvolutionWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| 💰 Webhooks de Pagamento — Fase 1
|--------------------------------------------------------------------------
*/

// Super Admin → Asaas cobra o Tenant
// URL: dominio.com/api/webhooks/asaas
// Header: asaas-access-token: {ASAAS_WEBHOOK_TOKEN}
Route::middleware('throttle:60,1')->post('/webhooks/asaas', [\App\Http\Controllers\Webhooks\AsaasWebhookController::class, 'handle'])
    ->name('webhooks.asaas');

// Tenant → Asaas/EFI notifica pagamento do cliente final
// URL: dominio.com/api/webhooks/pix/{webhook_token}
// O {webhook_token} é um UUID único por tenant, gerado no setup do gateway
Route::middleware('throttle:60,1')->post('/webhooks/pix/{webhookToken}', [\App\Http\Controllers\Webhooks\PixWebhookController::class, 'handle'])
    ->name('webhooks.pix')
    ->where('webhookToken', '[a-f0-9\-]{36}'); // UUID format

// Webhook para retorno da Prefeitura/SEFAZ (via provedor de NFS-e)
Route::middleware('throttle:30,1')->post('/webhooks/nfse/{tenant_id}', [\App\Http\Controllers\WebhookNFSeController::class, 'handle'])
    ->name('webhooks.nfse');
