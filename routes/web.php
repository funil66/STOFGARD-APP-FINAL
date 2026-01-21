<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\OrcamentoPdfController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PixWebhookController;
use App\Http\Controllers\CadastroController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rota de login pública usada como destino de redirecionamento quando middleware auth chama route('login')
Route::get('/login', function () {
    // Redireciona para a URL de login do Filament (se disponível)
    return redirect(\Filament\Facades\Filament::getLoginUrl());
})->name('login');

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

// Compatibility: accept POSTs to /admin/login for clients that submit a traditional
// form (non-Livewire). Enforce CSRF; this endpoint performs the authentication
// attempt and redirects back with errors on failure.
Route::post('admin/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $key = Str::lower($request->ip() . '|' . $request->input('email'));

    // Limit to 5 attempts per minute per IP+email
    if (RateLimiter::tooManyAttempts($key, 5)) {
        return back()->withErrors(['email' => __('Too many attempts. Please try again later.')])->setStatusCode(429);
    }

    if (! \Filament\Facades\Filament::auth()->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        RateLimiter::hit($key, 60);
        return back()->withErrors(['email' => __('filament-panels::pages/auth/login.messages.failed')]);
    }

    RateLimiter::clear($key);

    $request->session()->regenerate();

    return redirect()->intended(\Filament\Facades\Filament::getUrl());
});

// Temporary debug endpoint (local only) to inspect session and CSRF state.
Route::get('debug/session', function (Request $request) {
    if (! app()->environment('local')) {
        abort(404);
    }

    return response()->json([
        'session_id' => $request->session()->getId(),
        'session_token' => $request->session()->token(),
        'x_xsrf_token_header' => $request->header('X-XSRF-TOKEN'),
        'cookies' => $request->cookies->all(),
    ]);
});

// Serve Livewire published assets from public/vendor/livewire for compatibility
Route::get('livewire/{file}', function ($file) {
    $path = public_path('vendor/livewire/' . $file);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, ['Content-Type' => 'application/javascript']);
})->where('file', '.*');

// Local-only helper: create or update a test admin user with the provided credentials.
Route::get('debug/ensure-admin-user', function (Request $request) {
    if (! app()->environment('local')) {
        abort(404);
    }

    $user = \App\Models\User::updateOrCreate(
        ['email' => 'allisson@stofgard.com'],
        ['name' => 'Allisson', 'password' => \Illuminate\Support\Facades\Hash::make('Swordfish')]
    );

    return response()->json(['created' => (bool) $user, 'email' => $user->email]);
});


// Compatibilidade para quem acessa diretamente /admin/dashboard
Route::redirect('/admin/dashboard', '/admin')->name('admin.dashboard.redirect');



// Redirecionamentos para compatibilidade com URLs antigas do módulo Clientes
// As rotas e páginas do recurso Cliente foram removidas em favor do recurso
// unificado `Cadastros`. Mantemos esses redirects para evitar links quebrados.
Route::redirect('/admin/clientes', '/admin/cadastros')->name('admin.clientes.redirect');
Route::redirect('/admin/clientes/{any}', '/admin/cadastros')->where('any', '.*');

// Named redirects to satisfy Filament resource route lookups (prevents RouteNotFoundException)
Route::any('/admin/clientes', fn () => redirect('/admin/cadastros'))
    ->name('filament.admin.resources.clientes.index');

Route::any('/admin/clientes/create', fn () => redirect('/admin/cadastros'))
    ->name('filament.admin.resources.clientes.create');

Route::any('/admin/clientes/{record}', fn ($record) => redirect('/admin/cadastros'))
    ->where('record', '.*')
    ->name('filament.admin.resources.clientes.view');

Route::any('/admin/clientes/{record}/edit', fn ($record) => redirect('/admin/cadastros'))
    ->where('record', '.*')
    ->name('filament.admin.resources.clientes.edit');

// Rotas de autenticação do Google Calendar
Route::middleware(['auth'])->group(function () {
    Route::get('/google/auth', [GoogleCalendarController::class, 'redirectToGoogle'])
        ->name('google.auth');
    Route::get('/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])
        ->name('google.callback');

    // Rota para visualizar PDF do orçamento
    Route::get('/orcamento/{orcamento}/pdf', [OrcamentoPdfController::class, 'gerarPdf'])
        ->name('orcamento.pdf');

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

// Debug route: retorna JSON com clientes e itens (temporária)
Route::get('/debug/orcamento-data', function () {
    if (! app()->environment('local') && ! auth()->check()) {
        abort(403);
    }

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
    if (! app()->environment('local') && ! auth()->check()) {
        abort(403);
    }

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

// Local-only debug: report PHP upload/post limits used by the webserver
Route::get('/debug/php-ini', function () {
    if (! app()->environment('local')) {
        abort(404);
    }

    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
    ]);
});

// Local-only upload tester: GET shows a simple form, POST accepts multipart file field 'file' and saves it to storage/app/debug-uploads
Route::get('/debug/upload-test-form', function (\Illuminate\Http\Request $request) {
    if (! app()->environment('local')) {
        abort(404);
    }

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

Route::post('/debug/upload-test', function (\Illuminate\Http\Request $request) {
    if (! app()->environment('local')) {
        abort(404);
    }

    try {
        \Illuminate\Support\Facades\Log::info('Debug upload request', [
            'path' => $request->path(),
            'method' => $request->method(),
            'session_id' => $request->session()->getId(),
            'session_token' => $request->session()->token(),
            'x_xsrf_header' => $request->header('X-XSRF-TOKEN'),
            'headers' => $request->headers->all(),
            'cookies' => $request->cookies->all(),
            'content_length' => $request->server('CONTENT_LENGTH'),
            'content_type' => $request->server('CONTENT_TYPE'),
            'files' => array_map(function($f) { return [
                'name' => $f->getClientOriginalName() ?? null,
                'size' => $f->getSize() ?? null,
            ]; }, $request->files->all()),
            'raw_files' => isset($_FILES) ? $_FILES : null,
            'referer' => $request->header('Referer'),
            'user_agent' => $request->header('User-Agent'),
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Failed to log upload request: ' . $e->getMessage());
    }

    if (! $request->hasFile('file')) {
        return response()->json(['error' => 'no_file_provided'], 400);
    }

    $file = $request->file('file');

    // store in local disk
    $path = $file->store('debug-uploads');

    return response()->json([
        'stored_path' => $path,
        'size' => $file->getSize(),
        'original_name' => $file->getClientOriginalName(),
    ]);
});

// Alternative local-only endpoint that uses the API middleware (no CSRF/session) for testing
Route::post('/debug/upload-test-no-csrf', function (\Illuminate\Http\Request $request) {
    if (! app()->environment('local')) {
        abort(404);
    }

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

// Relatórios financeiros (autenticado)
Route::middleware(['auth'])->group(function () {
    Route::get('/financeiro/grafico/categoria', [\App\Http\Controllers\RelatorioFinanceiroController::class, 'graficoPorCategoria'])
        ->name('financeiro.grafico.categoria');
});

// Public: Cadastros (list, view, edit, update, delete). Edit/update/delete protected by auth.
Route::get('/cadastros', [CadastroController::class, 'index'])->name('cadastros.index');
Route::get('/cadastros/lojas', [CadastroController::class, 'lojas'])->name('cadastros.lojas');
Route::get('/cadastros/vendedores', [CadastroController::class, 'vendedores'])->name('cadastros.vendedores');
Route::get('/cadastros/{uuid}', [CadastroController::class, 'show'])->name('cadastros.show');
Route::get('/cadastros/{uuid}/edit', [CadastroController::class, 'edit'])->middleware('auth')->name('cadastros.edit');
Route::put('/cadastros/{uuid}', [CadastroController::class, 'update'])->middleware('auth')->name('cadastros.update');
Route::delete('/cadastros/{uuid}', [CadastroController::class, 'destroy'])->middleware('auth')->name('cadastros.destroy');
Route::delete('/cadastros', [CadastroController::class, 'bulkDestroy'])->middleware('auth')->name('cadastros.bulk.destroy');
Route::get('/cadastros/{uuid}/download', [CadastroController::class, 'downloadArquivos'])->middleware('auth')->name('cadastros.download');
Route::get('/cadastros/{uuid}/arquivo', [CadastroController::class, 'downloadArquivo'])->middleware('auth')->name('cadastros.arquivo.download');
Route::post('/cadastros/{uuid}/arquivo/delete', [CadastroController::class, 'destroyArquivo'])->middleware('auth')->name('cadastros.arquivo.delete');
