<?php

use App\Http\Controllers\CadastroPdfController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PixWebhookController;
use App\Http\Controllers\SuperAdmin\TenantUserController;
use App\Http\Controllers\Auth\JwtSessionBridgeController;
use App\Http\Controllers\Auth\EmpresaPasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - LIMPO E ORGANIZADO
|--------------------------------------------------------------------------
| Arquivo de rotas sem lógica de negócio - apenas definições de rotas
| apontando para Controllers. Toda lógica está em Services.
|--------------------------------------------------------------------------
*/

// Redirecionamento de segurança para o novo caminho do Super Admin
Route::redirect('/portal', '/super-admin');

// Página inicial
Route::view('/', 'welcome');

// Self-service company registration
Route::get('/registro-empresa', \App\Livewire\RegistroEmpresa::class)
    ->name('registro.empresa')
    ->middleware('throttle:10,1');

Route::get('/assinatura/{tenant}/status', \App\Http\Controllers\PublicSubscriptionStatusController::class)
    ->name('assinatura.status');

Route::get('/validar/{hash}', [\App\Http\Controllers\DigitalSealValidationController::class, 'show'])
    ->name('certificado.validar');

// Avaliação pública NPS (via token único enviado ao cliente)
Route::get('/avaliacao/{token}', \App\Livewire\AvaliacaoPublica::class)
    ->name('avaliacao.publica')
    ->middleware('throttle:30,1');

Route::post('/ping', function() {
    return response()->json(['status' => 'pong']);
});

// Forçando Livewire a usar a URL correta no proxy Cloudflare
\Livewire\Livewire::setUpdateRoute(function ($handle) {
    return Route::post('/livewire/update', $handle);
});
\Livewire\Livewire::setScriptRoute(function ($handle) {
    return Route::get('/livewire/livewire.js', $handle);
});

Route::middleware([\App\Http\Middleware\EnsureSuperAdmin::class])->group(function () {
    Route::get('/saas/tenant-users/create', [TenantUserController::class, 'create'])
        ->name('super-admin.tenant-users.create');

    Route::post('/saas/tenant-users', [TenantUserController::class, 'store'])
        ->name('super-admin.tenant-users.store');
});

// Rota de login JWT (Prestador)
Route::view('/login', 'auth.jwt-login')->name('empresa.login');
Route::post('/auth/central-login', [\App\Http\Controllers\Auth\CentralLoginController::class, 'submit'])
    ->name('auth.central.login');
Route::post('/auth/session-login', [JwtSessionBridgeController::class, 'store'])
    ->name('auth.session.login')
    ->withoutMiddleware([
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ]);

Route::get('/esqueci-senha', [EmpresaPasswordResetController::class, 'showRequestForm'])
    ->name('empresa.password.reset.request');
Route::post('/esqueci-senha', [EmpresaPasswordResetController::class, 'sendCode'])
    ->name('empresa.password.reset.send-code');
Route::get('/redefinir-senha', [EmpresaPasswordResetController::class, 'showResetForm'])
    ->name('empresa.password.reset.form');
Route::post('/redefinir-senha', [EmpresaPasswordResetController::class, 'reset'])
    ->name('empresa.password.reset.update');

// --- FASE 1: VITRINE PÚBLICA (Link na Bio) ---
// URL: stofgard.com.br/v/{slug-do-tenant}
Route::get('/v/{slug}', [\App\Http\Controllers\PublicProfileController::class, 'show'])
    ->name('tenant.profile');

// --- FASE 2: AGENDAMENTO PÚBLICO (Clone Calendly) ---
// URL: stofgard.com.br/agendar/{slug-do-tenant}
Route::prefix('agendar')->name('agendamento.')->group(function () {
    Route::get('/{slug}', [\App\Http\Controllers\AgendamentoPublicoController::class, 'show'])
        ->name('publico');
    Route::get('/{slug}/horarios', [\App\Http\Controllers\AgendamentoPublicoController::class, 'horariosDisponiveis'])
        ->name('horarios');
    Route::post('/{slug}/reservar', [\App\Http\Controllers\AgendamentoPublicoController::class, 'reservar'])
        ->name('reservar');
});

// --- FASE 3: PORTAL DO CLIENTE FINAL (Magic Link) ---
// O cliente não tem senha — acessa via link temporário enviado no WhatsApp
Route::prefix('cliente')->name('cliente.')->group(function () {
    // Consumir o magic link
    Route::get('/acesso/{token}', [\App\Http\Controllers\MagicLinkController::class, 'consumir'])
        ->name('magic-link.consumir');
    Route::get('/link-invalido', [\App\Http\Controllers\MagicLinkController::class, 'invalido'])
        ->name('magic-link.invalido');
    Route::post('/logout', [\App\Http\Controllers\MagicLinkController::class, 'logout'])
        ->name('logout');

    // Portal (protegido pelo middleware cliente.autenticado)
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

// --- DESENVOLVIMENTO LOCAL ---
if (app()->isLocal()) {
    Route::get('/dev-login', function () {
        $user = \App\Models\User::where('is_admin', true)->first()
            ?? \App\Models\User::first();

        if ($user) {
            auth()->login($user);

            return redirect('/admin');
        }

        return 'Nenhum usuário encontrado. Execute: php artisan migrate:fresh --seed';
    });
}

// --- CAPTAÇÃO DE LEADS (Público) ---
Route::get('/solicitar-orcamento', [LeadController::class, 'create'])
    ->name('solicitar.orcamento');

Route::post('/solicitar-orcamento', [LeadController::class, 'store'])
    ->name('solicitar.orcamento.post');

// Rota pública para visualizar PDF do orçamento via link assinado (WhatsApp)
Route::get('/orcamento/{orcamento}/compartilhar', [\App\Http\Controllers\OrcamentoPdfController::class, 'gerarPdf'])
    ->middleware('signed')
    ->name('orcamento.compartilhar');

// Rotas de autenticação do Google Calendar
Route::middleware(['auth'])->group(function () {
    Route::get('/google/auth', [GoogleCalendarController::class, 'redirectToGoogle'])
        ->name('google.auth');
    Route::get('/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])
        ->name('google.callback');
    // Rota para visualizar PDF do orçamento
    Route::get('/orcamento/{orcamento}/pdf', [\App\Http\Controllers\OrcamentoPdfController::class, 'gerarPdf'])
        ->name('orcamento.pdf');
    // Rota para visualizar PDF da OS
    Route::get('/os/{record}/pdf', [\App\Http\Controllers\OrdemServicoPdfController::class, 'gerarPdf'])
        ->name('os.pdf');

    // Rota para visualizar PDF da Agenda
    Route::get('/agenda/{agenda}/pdf', [\App\Http\Controllers\AgendaPdfController::class, 'gerarPdf'])
        ->name('agenda.pdf');

    // Rota para visualizar Ficha Cadastral (PDF)
    Route::get('/cadastro/{cadastro}/pdf', [CadastroPdfController::class, 'gerarPdf'])
        ->name('cadastro.pdf');

    // Rota para visualizar PDF do Financeiro
    Route::get('/financeiro/{financeiro}/pdf', [\App\Http\Controllers\FinanceiroPdfController::class, 'gerarPdf'])
        ->name('financeiro.pdf');
    Route::get('/financeiro/relatorio/mensal', [\App\Http\Controllers\FinanceiroPdfController::class, 'gerarRelatorioMensal'])
        ->name('financeiro.relatorio_mensal');
    Route::get('/extrato/pdf', [\App\Http\Controllers\ExtratoPdfController::class, 'gerarExtrato'])->name('extrato.pdf');

    // Rota para visualizar PDF da Nota Fiscal
    Route::get('/nota-fiscal/{notaFiscal}/pdf', [\App\Http\Controllers\NotaFiscalPdfController::class, 'gerarPdf'])
        ->name('nota-fiscal.pdf');

    // Rota para visualizar PDF da Categoria
    Route::get('/categoria/{categoria}/pdf', [\App\Http\Controllers\CategoriaPdfController::class, 'gerarPdf'])
        ->name('categoria.pdf');

    // Rota para visualizar PDF do Produto
    Route::get('/produto/{produto}/pdf', [\App\Http\Controllers\ProdutoPdfController::class, 'gerarPdf'])
        ->name('produto.pdf');

    // Rota para visualizar PDF da Tarefa
    Route::get('/tarefa/{tarefa}/pdf', [\App\Http\Controllers\TarefaPdfController::class, 'gerarPdf'])
        ->name('tarefa.pdf');

    // Rota para visualizar PDF do Equipamento
    Route::get('/equipamento/{equipamento}/pdf', [\App\Http\Controllers\EquipamentoPdfController::class, 'gerarPdf'])
        ->name('equipamento.pdf');

    // Rota para visualizar PDF da Garantia
    Route::get('/garantia/{garantia}/pdf', [\App\Http\Controllers\GarantiaPdfController::class, 'gerarPdf'])
        ->name('garantia.pdf');

    // Rota para visualizar PDF da Tabela de Preço
    Route::get('/tabelapreco/{tabelapreco}/pdf', [\App\Http\Controllers\TabelaPrecoPdfController::class, 'gerarPdf'])
        ->name('tabelapreco.pdf');

    // Rota para visualizar PDF da Lista de Desejos
    Route::get('/listadesejo/{listadesejo}/pdf', [\App\Http\Controllers\ListaDesejoPdfController::class, 'gerarPdf'])
        ->name('listadesejo.pdf');

    // Rota para visualizar PDF do Estoque
    Route::get('/estoque/{estoque}/pdf', [\App\Http\Controllers\EstoquePdfController::class, 'gerarPdf'])
        ->name('estoque.pdf');

    // Rota para gerar e salvar PDF (inclui opção include_pix e persist) removida

    // Arquivos (download / delete) - genérico para modelos que armazenam `arquivos` como JSON
    Route::get('/admin/files/download/{model}/{record}/{path}', [\App\Http\Controllers\ArquivosController::class, 'download'])
        ->name('admin.files.download');

    // Delete por URL assinada (signed route) para permitir exclusão via link sem CSRF
    Route::get('/admin/files/delete/{model}/{record}/{path}', [\App\Http\Controllers\ArquivosController::class, 'destroy'])
        ->middleware('signed')
        ->name('admin.files.delete');
});

// Rotas legadas de pagamento PIX (desativadas por padrão nos próprios controllers)
Route::get('/pagamento/{hash}', [PagamentoController::class, 'pix'])
    ->name('pagamento.pix');
Route::get('/pagamento/{hash}/verificar', [PagamentoController::class, 'verificarStatus'])
    ->name('pagamento.verificar');

// Webhook PIX legado (desativado por padrão no controller)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/webhook/pix', [PixWebhookController::class, 'handle'])
        ->name('webhook.pix');
    Route::get('/webhook/pix/status', [PixWebhookController::class, 'status'])
        ->name('webhook.pix.status');
});
// PIX status endpoint removed — use api/webhooks/pix instead

// NOTE: public debug PDF route removed for security. If you need a local-only test, use
// `scripts/generate-pdf.js` with debug HTML files under `storage/debug` or run the
// debug helper routes that remain protected by `app()->environment('local')` checks.

// Public file download route (no authentication required)
Route::get('/download/{disk}/{encodedPath}', [\App\Http\Controllers\FileDownloadController::class, 'download'])
    ->where(['encodedPath' => '.*'])
    ->name('file.download');

// Rota pública assinada para visualização inline de orçamento
Route::get('/orcamento/{orcamento}/publico', [\App\Http\Controllers\OrcamentoPdfController::class, 'stream'])
    ->middleware('signed')
    ->name('orcamento.public_stream');

// Relatórios financeiros (autenticado)
Route::middleware(['auth'])->group(function () {
    Route::get('/financeiro/grafico/categoria', [\App\Http\Controllers\RelatorioFinanceiroController::class, 'graficoPorCategoria'])
        ->name('financeiro.grafico.categoria');
});
