<?php

use App\Models\Cliente;
use App\Models\Orcamento;
use App\Services\StofgardSystem;
use App\Services\EstoqueService;
use App\Models\Financeiro;
use App\Models\OrdemServico;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- SIMPLE SYNC TEST START ---\n";

try {
    // 1. Setup
    $client = Cliente::first();
    if (!$client) {
        $client = Cliente::create(["nome" => "TestSync", "celular" => "11999999999"]);
    }

    $orc = Orcamento::create([
        "cadastro_id" => $client->id,
        "status" => "aberto",
        "valor_total" => 1000,
        "valor_final_editado" => 1000,
        "numero" => "SYNC-SIMPLE-" . time(),
        "data_orcamento" => now(),
        "data_validade" => now()->addDays(30),
        "id_parceiro" => 1 // Initial partner
    ]);

    echo "Orcamento created (ID: {$orc->id}). Approving...\n";

    // 2. Approve 
    $sys = new StofgardSystem(new EstoqueService());
    $sys->aprovarOrcamento($orc, 1, ["data_servico" => now()->addDays(2), "hora_inicio" => "09:00"]);

    $orc->refresh();
    $finReceita = $orc->financeiros()->where('tipo', 'entrada')->first();
    echo "Initial Financeiro Partner ID: " . ($finReceita->id_parceiro ?? 'NULL') . "\n";

    // 3. Test ID Sync
    echo "Updating Partner ID to 999...\n";
    $orc->update(["id_parceiro" => 999]);

    $finReceita->refresh();

    if ($finReceita->id_parceiro == 999) {
        echo "PASS: Financeiro Partner ID updated to 999.\n";
    } else {
        echo "FAIL: Financeiro Partner ID is {$finReceita->id_parceiro}\n";
    }

    // Cleanup
    $orc->financeiros()->delete();
    $orc->agendas()->delete();
    if ($orc->ordemServico)
        $orc->ordemServico->delete();
    $orc->delete();

} catch (\Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}

echo "--- SIMPLE SYNC TEST END ---\n";
