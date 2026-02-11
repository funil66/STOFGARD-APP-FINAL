<?php

// Load Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $pdfPath = storage_path('app/public/test_output_v2.pdf');

    echo "Tentando gerar PDF (V2)... \n";
    echo "Chrome Path: " . config('browsershot.chrome_path') . "\n";
    echo "Node Path: " . config('browsershot.node_path') . "\n";
    echo "NPM Path: " . config('browsershot.npm_path') . "\n";

    $pdf = \Spatie\LaravelPdf\Facades\Pdf::html('<h1>Teste de PDF V2</h1><p>Gerado em: ' . date('Y-m-d H:i:s') . '</p>')
        ->format('a4')
        ->name('test_output_v2.pdf');

    $pdf->withBrowsershot(function ($browsershot) {
        $browsershot->noSandbox()
            ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--headless'])
            ->timeout(60);

        // Explicitly set paths from config
        if (config('browsershot.chrome_path')) {
            $browsershot->setChromePath(config('browsershot.chrome_path'));
        }

        if (config('browsershot.node_path')) {
            $browsershot->setNodeBinary(config('browsershot.node_path'));
        }

        if (config('browsershot.npm_path')) {
            $browsershot->setNpmBinary(config('browsershot.npm_path'));
        }
    });

    $pdf->save($pdfPath);

    echo "PDF gerado com sucesso em: $pdfPath\n";
    echo "Tamanho do arquivo: " . filesize($pdfPath) . " bytes\n";

} catch (\Exception $e) {
    echo "ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
