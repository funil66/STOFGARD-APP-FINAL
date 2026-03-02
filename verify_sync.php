<?php

use App\Models\Cliente;
use App\Models\Orcamento;
use App\Services\StofgardSystem;
use App\Services\EstoqueService;
use App\Models\Financeiro;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- SYNC TEST START ---\n";

try {
    DB::beginTransaction();

    // 1. Setup
    $client = Cliente::first();
    if (!$client) {
        $client = Cliente::create(["nome" => "TestSync", "celular" => "11999999999"]);
    }

    $orc = Orcamento::create([
        "cadastro_id" => $client->id,
        "status" => "aberto", // Start open
        "valor_total" => 1000,
        "valor_final_editado" => 1000,
        "comissao_vendedor" => 50,
        "numero" => "SYNC-TEST-" . time(),
        "data_orcamento" => now(),
        "data_validade" => now()->addDays(30)
    ]);

    echo "Orcamento created (ID: {$orc->id}). Approving...\n";

    // 2. Approve (Generate OS/Fin/Agenda)
    $sys = new StofgardSystem(new EstoqueService());
    $sys->aprovarOrcamento($orc, 1, ["data_servico" => now()->addDays(2), "hora_inicio" => "09:00"]);

    $orc->refresh();
    $os = $orc->ordemServico;
    $finReceita = $orc->financeiros()->where('tipo', 'entrada')->first();
    $finComissao = $orc->financeiros()->where('tipo', 'saida')->where('is_comissao', true)->first();
    $agenda = $orc->agendas()->first();

    echo "Approved. OS ID: {$os->id}, Fin ID: {$finReceita->id}, Com ID: " . ($finComissao->id ?? 'N/A') . "\n";

    // 3. Test Value Sync
    echo "Editing Value to 1500...\n";
    $orc->update(["valor_total" => 1500, "valor_final_editado" => 1500]);

    $finReceita->refresh();
    $os->refresh();

    if ($finReceita->valor == 1500 && $os->valor_total == 1500) {
        echo "PASS: Value Sync (Fin: {$finReceita->valor}, OS: {$os->valor_total})\n";
    } else {
        echo "FAIL: Value Sync (Fin: {$finReceita->valor}, OS: {$os->valor_total})\n";
    }

    // 4. Test Commission Sync
    echo "Editing Commission to 100...\n";
    $orc->update(["comissao_vendedor" => 100]);

    if ($finComissao) {
        $finComissao->refresh();
        if ($finComissao->valor == 100) {
            echo "PASS: Commission Sync (Val: {$finComissao->valor})\n";
        } else {
            echo "FAIL: Commission Sync (Val: {$finComissao->valor})\n";
        }
    } else {
        echo "FAIL: No commission record found to sync.\n";
    }

    // 5. Test ID Sync
    echo "Editing Partner ID to 999...\n";
    $orc->update(["id_parceiro" => 999]);

    $os->refresh();
    $finReceita->refresh();
    $agenda->refresh();

    if ($os->id_parceiro == 999 && $finReceita->id_parceiro == 999 && $agenda->id_parceiro == 999) {
        echo "PASS: ID Sync (OS: {$os->id_parceiro}, Fin: {$finReceita->id_parceiro}, Ag: {$agenda->id_parceiro})\n";
    } else {
        echo "FAIL: ID Sync (OS: {$os->id_parceiro}, Fin: {$finReceita->id_parceiro}, Ag: {$agenda->id_parceiro})\n";
    }

    DB::rollBack();
    echo "Rolled back transaction.\n";

} catch (\Throwable $e) {
    echo "ERR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    DB::rollBack();
}

echo "--- SYNC TEST END ---\n";
