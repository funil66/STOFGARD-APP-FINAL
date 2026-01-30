<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\OrcamentoPdfController;
use App\Http\Controllers\CadastroPdfController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PixWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rotas de autenticação do Google Calendar
Route::middleware(['auth'])->group(function () {
    Route::get('/google/auth', [GoogleCalendarController::class, 'redirectToGoogle'])
        ->name('google.auth');
    Route::get('/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])
        ->name('google.callback');

    // Rota para visualizar PDF do orçamento
    Route::get('/orcamento/{orcamento}/pdf', [OrcamentoPdfController::class, 'gerarPdf'])
        ->name('orcamento.pdf');

    // Rota para visualizar Ficha Cadastral (PDF)
    Route::get('/cadastro/{cadastro}/pdf', [CadastroPdfController::class, 'gerarPdf'])
        ->name('cadastro.pdf');

    // Rota para gerar e salvar PDF (inclui opção include_pix e persist)
    Route::post('/orcamento/{orcamento}/generate-pdf', [OrcamentoPdfController::class, 'generateAndSave'])
        ->name('orcamento.generate');

    // Arquivos (download / delete) - genérico para modelos que armazenam `arquivos` como JSON
    Route::get('/admin/files/download/{model}/{record}/{path}', [\App\Http\Controllers\ArquivosController::class, 'download'])
        ->name('admin.files.download');

    // Delete por URL assinada (signed route) para permitir exclusão via link sem CSRF
    Route::get('/admin/files/delete/{model}/{record}/{path}', [\App\Http\Controllers\ArquivosController::class, 'destroy'])
        ->middleware('signed')
        ->name('admin.files.delete');
});

// Rotas públicas de pagamento PIX
Route::get('/pagamento/{hash}', [PagamentoController::class, 'pix'])
    ->name('pagamento.pix');
Route::get('/pagamento/{hash}/verificar', [PagamentoController::class, 'verificarStatus'])
    ->name('pagamento.verificar');

// Webhook PIX (EFI/Gerencianet)
Route::post('/webhook/pix', [PixWebhookController::class, 'handle'])
    ->name('webhook.pix');
Route::get('/webhook/pix/status', [PixWebhookController::class, 'status'])
    ->name('webhook.pix.status');

if (app()->environment('local')) {
    // Debug route: retorna JSON com clientes e itens (temporária)
    Route::get('/debug/orcamento-data', function () {
        $clientes = App\Filament\Resources\OrcamentoResource::getClientesOptions();
        $higi = App\Filament\Resources\OrcamentoResource::getItensHigienizacaoOptions();
        $imper = App\Filament\Resources\OrcamentoResource::getItensImpermeabilizacaoOptions();

        return response()->json([
            'clientes_count' => count($clientes),
            'clientes' => $clientes,
            'itens_higienizacao_count' => count($higi),
            'itens_higienizacao' => $higi,
            'itens_impermeabilizacao_count' => count($imper),
            'itens_impermeabilizacao' => $imper,
        ]);
    });

    // Debug: forçar geração de QR para um orçamento (apenas local)
    Route::get('/debug/orcamento/{id}/ensure-pix', function ($id) {
        $orc = App\Models\Orcamento::find($id);
        if (! $orc) {
            return response()->json(['error' => 'not_found'], 404);
        }

        // Se forma de pagamento é pix e não tem QR salvo, tenta gerar
        if ($orc->forma_pagamento === 'pix' && empty($orc->pix_qrcode_base64)) {
            app(App\Services\StaticPixQrCodeService::class)->generate($orc);
            $orc->refresh();
        }

        return response()->json([
            'id' => $orc->id,
            'forma_pagamento' => $orc->forma_pagamento,
            'pix_qrcode_base64_exists' => ! empty($orc->pix_qrcode_base64),
            'pix_qrcode_base64_preview' => $orc->pix_qrcode_base64 ? substr($orc->pix_qrcode_base64, 0, 40) : null,
            'pix_copia_cola' => $orc->pix_copia_cola,
            'pix_chave_tipo' => $orc->pix_chave_tipo,
            'pix_chave_valor' => $orc->pix_chave_valor,
        ]);
    });

    // Local-only upload tester: GET shows a simple form (POST upload removed for security)
    Route::get('/debug/upload-test-form', function (\Illuminate\Http\Request $request) {
        try {
            \Illuminate\Support\Facades\Log::info('Serving upload test form', [
                'path' => $request->path(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
                'session_token' => $request->session()->token(),
                'headers' => $request->headers->all(),
                'cookies' => $request->cookies->all(),
                'content_length' => $request->server('CONTENT_LENGTH'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to log upload form request: ' . $e->getMessage());
        }

        // Return a minimal HTML form to test multipart uploads from a browser (includes CSRF field)
        $html = '';
        $html .= '<!doctype html><html><head><meta charset="utf-8"><title>Upload Test</title></head><body>';
        $html .= '<h1>Local Upload Test</h1>';
        $html .= '<form method="POST" action="/debug/upload-test" enctype="multipart/form-data">';
        $html .= csrf_field();
        $html .= '<input type="file" name="file"> <button type="submit">Upload</button>';
        $html .= '</form></body></html>';

        return response($html, 200, ['Content-Type' => 'text/html']);
    });

    // Alternative local-only endpoint that uses the API middleware (no CSRF/session) for testing
    Route::post('/debug/upload-test-no-csrf', function (\Illuminate\Http\Request $request) {
        if (! $request->hasFile('file')) {
            return response()->json(['error' => 'no_file_provided'], 400);
        }

        $file = $request->file('file');

        $path = $file->store('debug-uploads');

        return response()->json([
            'stored_path' => $path,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ]);
    })->middleware('api')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    // Local-only debug: phpinfo() for troubleshooting which php.ini is loaded
    Route::get('/debug/phpinfo', function () {
        ob_start();
        phpinfo();
        $html = ob_get_clean();

        // Return a small subset: 'Loaded Configuration File' and SAPI
        preg_match('/Loaded Configuration File.*?>(.*?)<\/div>/is', $html, $m1);
        preg_match('/Server API.*?>(.*?)<\/div>/is', $html, $m2);

        return response()->json([
            'loaded_configuration_file' => $m1[1] ?? null,
            'server_api' => $m2[1] ?? php_sapi_name(),
        ]);
    });
}

// Local-only debug: phpinfo() for troubleshooting which php.ini is loaded
Route::get('/debug/phpinfo', function () {
    if (! app()->environment('local')) {
        abort(404);
    }

    ob_start();
    phpinfo();
    $html = ob_get_clean();

    // Return a small subset: 'Loaded Configuration File' and SAPI
    preg_match('/Loaded Configuration File.*?>(.*?)<\/div>/is', $html, $m1);
    preg_match('/Server API.*?>(.*?)<\/div>/is', $html, $m2);

    return response()->json([
        'loaded_configuration_file' => $m1[1] ?? null,
        'server_api' => $m2[1] ?? php_sapi_name(),
    ]);
});

// NOTE: public debug PDF route removed for security. If you need a local-only test, use
// `scripts/generate-pdf.js` with debug HTML files under `storage/debug` or run the
// debug helper routes that remain protected by `app()->environment('local')` checks.

// Public file download route (no authentication required)
Route::get('/download/{disk}/{encodedPath}', [\App\Http\Controllers\FileDownloadController::class, 'download'])
    ->where(['encodedPath' => '.*'])
    ->name('file.download');

// Rota pública assinada para o cliente baixar o PDF
Route::get('/orcamento/{orcamento}/publico', [OrcamentoPdfController::class, 'stream'])
    ->name('orcamento.public_stream')
    ->middleware('signed');

// Relatórios financeiros (autenticado)
Route::middleware(['auth'])->group(function () {
    Route::get('/financeiro/grafico/categoria', [\App\Http\Controllers\RelatorioFinanceiroController::class, 'graficoPorCategoria'])
        ->name('financeiro.grafico.categoria');
});
