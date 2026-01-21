<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// run migrations
Artisan::call('migrate:fresh', ['--force' => true]);
echo Artisan::output();

$cliente = App\Models\Cliente::factory()->create();
$user = App\Models\User::factory()->create();

$orcamento = App\Models\Orcamento::create([
    'cliente_id' => $cliente->id,
    'criado_por' => $user->id,
    'status' => 'aprovado',
    'data_servico_agendada' => now()->addDays(5),
    'numero_orcamento' => App\Models\Orcamento::gerarNumeroOrcamento(),
    'data_orcamento' => now(),
    'data_validade' => now()->addDays(7),
    'descricao_servico' => 'Teste',
]);

print_r($orcamento->toArray());

$os = App\Models\OrdemServico::where('orcamento_id', $orcamento->id)->first();
if ($os) {
    echo "OS created: "; print_r($os->toArray());
} else {
    echo "OS not created\n";
}
