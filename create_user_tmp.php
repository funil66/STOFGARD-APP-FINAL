<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::updateOrCreate(
    ['email' => 'allissonsousa.adv@gmail.com'],
    [
        'name' => 'Allisson Sousa',
        'password' => bcrypt('Swordfish66@'),
        'is_admin' => true,
        'is_super_admin' => true
    ]
);

echo "Usuario Super Admin: " . $user->email . " atualizado com sucesso!\n";
