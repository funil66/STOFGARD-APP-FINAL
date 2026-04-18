<?php
// smoke_test.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Inicializando Tenant 'madruga'...\n";
    tenancy()->initialize('madruga');

    $cadastro = \App\Models\Cadastro::create([
        'nome' => 'Dr. Maiden Teste',
        'documento' => '12345678901',
        'tipo' => 'cliente'
    ]);

    $os = \App\Models\OrdemServico::create([
        'cadastro_id' => $cadastro->id,
        'status' => 'aberta',
        'tipo_servico' => 'Teste LGPD',
        'descricao_servico' => 'Teste Blindagem Jurídica',
        'data_abertura' => now(),
        'valor_total' => 500
    ]);

    $request = \Illuminate\Http\Request::create('/test', 'POST', [], [], [], [
        'REMOTE_ADDR' => '185.182.185.58',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Smoke Test Iron Code V8)'
    ]);

    echo "Finalizando Assinatura Legal...\n";
    $action = app(\App\Actions\FinalizeAssinaturaAction::class);
    // 1 pixel base64 mock
    $mockSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    $result = $action->execute($os, $mockSignature, $request);

    $os->refresh();

    echo "--- TESTE 1: DR. MAIDEN (COMPLIANCE) ---\n";
    echo "OS ID: {$os->id}\n";
    echo "Assinatura Hash Existente: " . ($os->assinatura_pdf_hash ? 'SIM (' . $os->assinatura_pdf_hash . ')' : 'NAO') . "\n";
    echo "IP Capturado: " . ($os->assinatura_metadata['ip'] ?? 'NAO') . "\n";
    echo "User Agent: " . ($os->assinatura_metadata['user_agent'] ?? 'NAO') . "\n";

    echo "\n--- TESTE 2: MOTOR V8 (JOB PDF) ---\n";

    $orcamento = \App\Models\Orcamento::create([
        'cadastro_id' => $cadastro->id,
        'numero' => '1000',
        'data_orcamento' => now(),
        'data_validade' => now()->addDays(10),
        'status' => 'aprovado',
        'valor_total' => 1500
    ]);

    echo "Despachando Job GenerateAndSendPdfJob para o Orcamento {$orcamento->id}...\n";
    \App\Jobs\GenerateAndSendPdfJob::dispatch($orcamento->id);

    echo "Job na Fila com Sucesso!\n";

} catch (\Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
