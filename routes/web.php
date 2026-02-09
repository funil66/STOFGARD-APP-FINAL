<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\OrcamentoPdfController;
use App\Http\Controllers\CadastroPdfController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PixWebhookController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - LIMPO E ORGANIZADO
|--------------------------------------------------------------------------
| Arquivo de rotas sem lógica de negócio - apenas definições de rotas
| apontando para Controllers. Toda lógica está em Services.
|--------------------------------------------------------------------------
*/

// Página inicial
Route::view('/', 'welcome');

// Rota de login (redireciona para Filament)
Route::redirect('/login', '/admin/login')->name('login');

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
Route::get('/orcamento/{orcamento}/compartilhar', [OrcamentoPdfController::class, 'stream'])
    ->middleware('signed')
    ->name('orcamento.compartilhar');

// Rotas de autenticação do Google Calendar
Route::middleware(['auth'])->group(function () {
    Route::get('/google/auth', [GoogleCalendarController::class, 'redirectToGoogle'])
        ->name('google.auth');
    Route::get('/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])
        ->name('google.callback');

    // Rota para visualizar PDF do orçamento
    Route::get('/orcamento/{orcamento}/pdf', [OrcamentoPdfController::class, 'gerarPdf'])
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


