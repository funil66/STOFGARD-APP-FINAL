<?php
require __DIR__ . '/../vendor/autoload.php';

// Bring up the app
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Run migrations
echo "Running migrations...\n";
Artisan::call('migrate:fresh', ['--force' => true]);
echo Artisan::output();

// Create test records
$parceiro = App\Models\Parceiro::create(['nome' => 'Parceiro Test', 'tipo' => 'loja', 'registrado_por' => 'script']);
$cliente = App\Models\Cliente::factory()->create();

// Create Financeiro via model
$f1 = App\Models\Financeiro::create([
    'tipo' => 'entrada',
    'descricao' => 'Recebimento Parceiro',
    'valor' => 250.00,
    'data' => now()->format('Y-m-d'),
    'cadastro_id' => 'parceiro_' . $parceiro->id,
]);

$f2 = App\Models\Financeiro::create([
    'tipo' => 'entrada',
    'descricao' => 'Recebimento Cliente',
    'valor' => 150.00,
    'data' => now()->format('Y-m-d'),
    'cadastro_id' => 'cliente_' . $cliente->id,
]);

$rows = DB::table('financeiros')->get();
print_r($rows->toArray());
