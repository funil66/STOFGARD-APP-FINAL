<?php
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$valid = \Illuminate\Support\Facades\Hash::check('Mariagard2026@', $user->password);
echo "Valid: " . ($valid ? 'true' : 'false') . "\n";
echo "Tenant ID: " . $user->tenant_id . "\n";
