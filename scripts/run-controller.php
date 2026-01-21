<?php
// Script de teste: chama o controller de PDF diretamente dentro do container
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Orcamento;
use App\Services\PixService;

$orc = Orcamento::find(5);
if (! $orc) {
    echo "Orcamento 5 não encontrado\n";
    exit(2);
}

try {
    $controller = app(App\Http\Controllers\OrcamentoPdfController::class);
    $response = $controller->show($orc, app(PixService::class));
    echo get_class($response) . "\n";
    // Se for BinaryFileResponse, imprimir o caminho do arquivo (quando aplicável)
    if (method_exists($response, 'getFile')) {
        $file = $response->getFile();
        echo "File: " . (is_string($file) ? $file : (method_exists($file, 'getPathname') ? $file->getPathname() : var_export($file, true))) . "\n";
    }
    exit(0);
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
