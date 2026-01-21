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
echo json_encode([
    'id' => $u->id,
    'email' => $u->email,
    'name' => $u->name,
    'password' => $u->password,
    'created_at' => (string) $u->created_at,
]);
echo "\n";