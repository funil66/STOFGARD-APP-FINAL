<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

try {
    $u = User::firstOrNew(['email' => 'allissonsousa.adv@gmail.com']);
    $u->name = 'Allisson Sousa';
    $u->password = \Illuminate\Support\Facades\Hash::make('Swordfish66@');
    $u->is_super_admin = true;
    $u->is_admin = true;
    $u->tenant_id = null;
    $u->save();
    echo "Super Admin restaurado com sucesso! E-mail: " . $u->email . "\n";
} catch (\Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
