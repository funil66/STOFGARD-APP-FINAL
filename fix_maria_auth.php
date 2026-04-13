<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'maria@stofgard.com.br')->first();
$user->password = Hash::make('password123');
$user->save();
echo "Updated password. Auth::attempt = ";
var_dump(Auth::attempt(['email' => 'maria@stofgard.com.br', 'password' => 'password123']));
