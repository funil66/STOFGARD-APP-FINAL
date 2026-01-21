<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Remove usuário antigo
\App\Models\User::where('email', 'allisson@stofgard.com')->delete();

// Cria novo usuário admin
$u = new \App\Models\User();
$u->name = 'Allisson';
$u->email = 'allisson@stofgard.com';
$u->password = 'Swordfish';
$u->is_admin = true;
$u->save();
echo json_encode(['id' => $u->id, 'email' => $u->email, 'password' => $u->password]);
