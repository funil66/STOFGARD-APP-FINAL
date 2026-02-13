<?php

use App\Models\Estoque;
use App\Models\OrdemServico;
use App\Services\StofgardSystem;
use Illuminate\Support\Facades\DB;
use App\Models\Cadastro;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Iniciando Teste de Reprodução de Bug de Estoque ---\n";

try {
    // 1. Setup: Criar Item de Estoque
    echo "[DEBUG] Criando item de estoque...\n";
    $estoque = Estoque::create([
        'item' => 'Item Teste Bug ' . uniqid(),
        'quantidade' => 100,
        'unidade' => 'un',
        'minimo_alerta' => 10,
        'tipo' => 'produto',
        'preco_interno' => 10.00,
        'preco_venda' => 20.00,
    ]);

    echo "Estoque Inicial: {$estoque->quantidade}\n";

    // 2. Setup: Criar Cliente Dummy
    echo "[DEBUG] Criando cliente...\n";
    $cliente = Cadastro::create([
        'nome' => 'Cliente Teste',
        'tipo' => 'cliente',
        'documento' => '00000000000',
    ]);

    // 3. Criar OS
    echo "[DEBUG] Criando OS...\n";
    $os = OrdemServico::create([
        'cadastro_id' => $cliente->id,
        'status' => 'aberta',
        'data_abertura' => now(),
        'numero_os' => 'TEST-' . uniqid(),
    ]);

    // 4. Adicionar Item à OS
    echo "[DEBUG] Adicionando item à OS...\n";
    $os->produtosUtilizados()->attach($estoque->id, [
        'quantidade_utilizada' => 1,
        'unidade' => 'un'
    ]);

    // Forçar o evento saved para disparar a lógica do Observer de estoque (simulando request)
    request()->merge([
        'produtosUtilizados' => [
            $estoque->id => ['quantidade_utilizada' => 1]
        ]
    ]);

    echo "[DEBUG] Disparando Observer...\n";
    $observer = new \App\Observers\OrdemServicoObserver();
    $observer->saved($os);

    $estoque->refresh();
    echo "Estoque após adicionar à OS (Esperado 99): {$estoque->quantidade}\n";

    if ($estoque->quantidade != 99) {
        echo "ALERTA: O Observer não baixou o estoque corretamente na criação. Atual: {$estoque->quantidade}\n";
    }

    // 5. Finalizar OS
    echo "Finalizando OS...\n";
    $sistema = app(StofgardSystem::class);
    $sistema->finalizarOS($os);
    echo "[DEBUG] OS Finalizada.\n";

    $estoque->refresh();
    echo "Estoque após finalizar OS (Esperado 99, Bug seria 98): {$estoque->quantidade}\n";

    if ($estoque->quantidade == 98) {
        echo "🔴 BUG CONFIRMADO: O estoque foi baixado duas vezes!\n";
    } elseif ($estoque->quantidade == 99) {
        echo "🟢 SUCESSO: O estoque foi baixado apenas uma vez.\n";
    } else {
        echo "🟡 Resultado inesperado: {$estoque->quantidade}\n";
    }

    // Cleanup
    echo "[DEBUG] Cleaning up...\n";
    $os->delete();
    $cliente->delete();
    $estoque->delete();

} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
