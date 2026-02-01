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

// --- ROTA PÚBLICA DE CAPTAÇÃO DE LEADS ---
if (app()->isLocal()) {
    Route::get('/dev-login', function () {
        $user = \App\Models\User::where('is_admin', true)->first();
        if (!$user) {
            $user = \App\Models\User::first();
        }

        if ($user) {
            auth()->login($user);
            return redirect('/admin');
        }

        return 'Nenhum usuário encontrado. Execute: php artisan migrate:fresh --seed';
    });
}

Route::get('/solicitar-orcamento', function () {
    return view('landing.solicitar-orcamento');
})->name('solicitar.orcamento');

Route::post('/solicitar-orcamento', function (\Illuminate\Http\Request $request) {
    // 1. Validação Simples
    $dados = $request->validate([
        'nome' => 'required|string|max:255',
        'celular' => 'required|string|max:20',
        'servico' => 'required|string',
        'cidade' => 'required|string',
    ]);

    // 2. Busca ou Cria Cliente (Pelo Celular)
    // Limpa caracteres do celular para busca
    $celularLimpo = preg_replace('/\D/', '', $dados['celular']);

    // Tenta achar cliente existente (busca flexível)
    $cliente = \App\Models\Cadastro::where('celular', 'LIKE', "%{$celularLimpo}%")
        ->orWhere('celular', $dados['celular'])
        ->first();

    if (!$cliente) {
        $cliente = \App\Models\Cadastro::create([
            'nome' => $dados['nome'],
            'celular' => $dados['celular'],
            'cidade' => $dados['cidade'], // Campo simples ou observação
            'tipo' => 'cliente',
            // Campos obrigatórios podem precisar de default
            'email' => 'lead.' . time() . '@temp.com', // Placeholder se email for unique/required
        ]);
    }

    // 3. Cria Orçamento na etapa "Novo Lead" do Funil
    $orcamento = \App\Models\Orcamento::create([
        'cadastro_id' => $cliente->id,
        'data_orcamento' => now(),
        'data_validade' => now()->addDays(7),
        'status' => 'rascunho', // Status técnico
        'etapa_funil' => 'novo', // Status do Kanban
        'tipo_servico' => $dados['servico'],
        'criado_por' => 'Sistema (Lead Page)',
        'valor_total' => 0.00, // Inicializa com 0.00
        'observacoes' => "Solicitação via Site.\nCidade: {$dados['cidade']}\nInteresse: {$dados['servico']}",
    ]);

    // 4. Retorno visual
    return back()->with('success', 'Sua solicitação foi enviada! Em breve entraremos em contato.');
})->name('solicitar.orcamento.post');

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
