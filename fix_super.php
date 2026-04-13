<?php
require_once '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'allissonsousa.adv@gmail.com';
$password = 'Swordfish66@';

$user = \App\Models\User::firstOrNew(['email' => $email]);
$user->name = 'Super Admin';
$user->password = \Illuminate\Support\Facades\Hash::make($password);
$user->is_super_admin = true;
$user->is_admin = true;
$user->email_verified_at = now();
$user->save();

echo "User created";
