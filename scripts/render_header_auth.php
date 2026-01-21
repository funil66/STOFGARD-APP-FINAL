<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Login as user id 5 (adjust if different)
$user = \App\Models\User::where('email', 'allisson@stofgard.com')->first();
if (! $user) {
    echo "User not found\n";
    exit(1);
}
\Illuminate\Support\Facades\Auth::login($user);

echo view('filament.components.header')->render();
