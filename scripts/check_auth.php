<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$u = \App\Models\User::where('email', 'allisson@stofgard.com')->first();
if (! $u) {
    echo "NOT_FOUND\n";
    exit(0);
}
echo "User exists\n";
echo "Hash check Swordfish: " . (\Illuminate\Support\Facades\Hash::check('Swordfish', $u->password) ? 'OK' : 'FAIL') . "\n";
try {
    $res = \Filament\Facades\Filament::auth()->attempt(['email' => 'allisson@stofgard.com', 'password' => 'Swordfish']);
    echo 'Filament auth attempt: ' . ($res ? 'OK' : 'FAIL') . "\n";
} catch (\Throwable $e) {
    echo 'Filament attempt exception: ' . $e->getMessage() . "\n";
}
